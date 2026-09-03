<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Models\Customsetting;
use Symfony\Component\HttpFoundation\IpUtils;
use Dashed\DashedCore\Mail\CmsIpAllowlistChangedMail;

/**
 * De lijst met IP-adressen waarvandaan het CMS bereikbaar is.
 *
 * Leeg betekent: geen beperking. Zodra er iets in staat krijgt elk verzoek aan
 * het paneel vanaf een ander adres een 403, de inlogpagina inbegrepen. Een
 * regel is een los adres (IPv4 of IPv6) of een reeks in CIDR-notatie, met een
 * naam ervoor zodat je terugziet van wie een adres is (kantoor, thuis, een VPN).
 *
 * Opgeslagen als één regel per adres, `naam|adres`; een adres zonder naam staat
 * er kaal. Dat oude, kale formaat blijft dus gewoon geldig: een lijst van vóór
 * de namen leest in als adressen zonder naam, klaar om er een naam bij te typen.
 *
 * De lijst is één instelling voor de hele installatie, niet per site: een
 * IP-beperking is een eigenschap van de server, niet van een website. Hij staat
 * daarom uitdrukkelijk op de eerste site. Customsetting::get() zonder site valt
 * terug op de actieve site en Customsetting::set() op de eerste, en dat
 * verschil zou de lijst anders per site laten zwerven.
 */
class CmsIpAllowlist
{
    public const SETTING = 'cms_allowed_ips';

    public static function siteId(): string
    {
        return (string) Sites::getFirstSite()['id'];
    }

    /**
     * De adressen met hun naam, in de volgorde waarin ze zijn ingevoerd.
     *
     * @return array<int, array{name: string, ip: string}>
     */
    public static function entries(): array
    {
        return self::parse((string) Customsetting::get(self::SETTING, self::siteId(), ''));
    }

    /**
     * Alleen de adressen, voor de daadwerkelijke controle.
     *
     * @return array<int, string>
     */
    public static function ips(): array
    {
        return array_values(array_map(fn (array $entry) => $entry['ip'], self::entries()));
    }

    public static function isActive(): bool
    {
        return count(self::entries()) > 0;
    }

    public static function allows(?string $ip): bool
    {
        $ips = self::ips();

        if (! $ips) {
            return true;
        }

        if (! $ip) {
            return false;
        }

        return IpUtils::checkIp($ip, $ips);
    }

    /**
     * Regel voor regel. Binnen een regel splitst de eerste `|` de naam van het
     * adres. Een regel zonder `|` is een naamloos adres; zo'n regel mag nog
     * meerdere adressen bevatten, gescheiden door komma of spatie, zodat een
     * lijst die uit een e-mail of firewallregel geplakt wordt ook werkt.
     *
     * @return array<int, array{name: string, ip: string}>
     */
    public static function parse(string $raw): array
    {
        $entries = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_contains($line, '|')) {
                [$name, $ip] = array_map('trim', explode('|', $line, 2));

                if ($ip !== '') {
                    $entries[] = ['name' => self::cleanName($name), 'ip' => $ip];
                }

                continue;
            }

            foreach (preg_split('/[\s,]+/', $line) ?: [] as $ip) {
                $ip = trim($ip);

                if ($ip !== '') {
                    $entries[] = ['name' => '', 'ip' => $ip];
                }
            }
        }

        return self::dedupe($entries);
    }

    /**
     * Aanvaardt zowel de gestructureerde vorm (['name' => , 'ip' => ]) als een
     * kaal adres als string, zodat de commandoregel en oude aanroepers gewoon
     * een adres kunnen meegeven.
     *
     * @param  array<int, array{name?: string, ip?: string}|string>  $entries
     * @return array<int, array{name: string, ip: string}>
     */
    public static function normalize(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $entry = ['name' => '', 'ip' => $entry];
            }

            $ip = trim((string) ($entry['ip'] ?? ''));

            if ($ip === '') {
                continue;
            }

            $normalized[] = ['name' => self::cleanName((string) ($entry['name'] ?? '')), 'ip' => $ip];
        }

        return self::dedupe($normalized);
    }

    /**
     * @param  array<int, array{name: string, ip: string}>  $entries
     */
    public static function format(array $entries): string
    {
        return implode("\n", array_map(
            fn (array $entry) => $entry['name'] !== '' ? $entry['name'] . '|' . $entry['ip'] : $entry['ip'],
            $entries,
        ));
    }

    /**
     * De adressen die geen geldig IP of geldige CIDR-reeks zijn.
     *
     * @param  array<int, array{name?: string, ip?: string}|string>  $entries
     * @return array<int, string>
     */
    public static function invalidEntries(array $entries): array
    {
        return array_values(array_filter(
            array_map(fn (array $entry) => $entry['ip'], self::normalize($entries)),
            fn (string $ip) => ! self::isValidEntry($ip),
        ));
    }

    public static function isValidEntry(string $entry): bool
    {
        if (! str_contains($entry, '/')) {
            return filter_var($entry, FILTER_VALIDATE_IP) !== false;
        }

        [$address, $prefix] = explode('/', $entry, 2) + [1 => null];

        if ($prefix === null || ! ctype_digit($prefix)) {
            return false;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return (int) $prefix <= 32;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return (int) $prefix <= 128;
        }

        return false;
    }

    /**
     * @param  array<int, array{name?: string, ip?: string}|string>  $entries
     */
    public static function save(array $entries): void
    {
        $entries = self::normalize($entries);
        $previous = self::entries();

        Customsetting::set(self::SETTING, self::format($entries), self::siteId());

        if ($entries !== $previous) {
            self::notifySuperadmins($previous, $entries);
        }
    }

    /**
     * Elke superadmin hoort het zodra de lijst verandert, ook vanaf de
     * commandoregel. Wie het CMS op adres afsluit of juist weer openzet, hoort
     * dat niet alleen zelf te weten.
     *
     * @param  array<int, array{name: string, ip: string}>  $previous
     * @param  array<int, array{name: string, ip: string}>  $current
     */
    protected static function notifySuperadmins(array $previous, array $current): void
    {
        $user = auth()->user();
        $actor = $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? $user->email)
            : 'de commandoregel';

        // Per ontvanger een eigen mailable: Mail::to()->send() voegt het adres toe
        // aan de ontvangers van het object, dus één gedeeld object stapelt op
        // (mail 1 naar A, mail 2 naar A én B, ...) en iedereen krijgt hem vaker.
        $mail = fn () => new CmsIpAllowlistChangedMail(
            oldEntries: self::labels($previous),
            newEntries: self::labels($current),
            actor: $actor . ($user?->email ? ' (' . $user->email . ')' : ''),
            actorIp: app()->runningInConsole() ? null : request()->ip(),
            changedAt: now()->format('d-m-Y H:i'),
        );

        User::query()
            ->where('role', 'superadmin')
            ->whereNotNull('email')
            ->get()
            ->each(fn (User $superadmin) => Mail::to($superadmin->email)->send($mail()));
    }

    /**
     * Leesbare regels voor mail en commandoregel: "naam - adres", of alleen het
     * adres als er geen naam bij staat.
     *
     * @param  array<int, array{name: string, ip: string}>  $entries
     * @return array<int, string>
     */
    public static function labels(array $entries): array
    {
        return array_map(
            fn (array $entry) => $entry['name'] !== '' ? $entry['name'] . ' - ' . $entry['ip'] : $entry['ip'],
            $entries,
        );
    }

    public static function clear(): void
    {
        self::save([]);
    }

    /**
     * Eerste voorkomen per adres wint, zodat de naam van de eerste regel
     * behouden blijft als hetzelfde adres twee keer voorkomt.
     *
     * @param  array<int, array{name: string, ip: string}>  $entries
     * @return array<int, array{name: string, ip: string}>
     */
    protected static function dedupe(array $entries): array
    {
        $seen = [];
        $result = [];

        foreach ($entries as $entry) {
            if (isset($seen[$entry['ip']])) {
                continue;
            }

            $seen[$entry['ip']] = true;
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Een naam mag geen `|` of nieuwe regel bevatten: daarmee zou hij het
     * opslagformaat, één adres per regel met `|` als scheiding, kapotmaken.
     */
    protected static function cleanName(string $name): string
    {
        return trim(str_replace(['|', "\r", "\n"], ' ', $name));
    }
}

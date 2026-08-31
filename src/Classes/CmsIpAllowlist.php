<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\Customsetting;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * De lijst met IP-adressen waarvandaan het CMS bereikbaar is.
 *
 * Leeg betekent: geen beperking. Zodra er iets in staat krijgt elk verzoek aan
 * het paneel vanaf een ander adres een 403, de inlogpagina inbegrepen. Een
 * regel is een los adres (IPv4 of IPv6) of een reeks in CIDR-notatie.
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
     * @return array<int, string>
     */
    public static function entries(): array
    {
        return self::parse((string) Customsetting::get(self::SETTING, self::siteId(), ''));
    }

    public static function isActive(): bool
    {
        return count(self::entries()) > 0;
    }

    public static function allows(?string $ip): bool
    {
        $entries = self::entries();

        if (! $entries) {
            return true;
        }

        if (! $ip) {
            return false;
        }

        return IpUtils::checkIp($ip, $entries);
    }

    /**
     * Regels, komma's en spaties gelden allemaal als scheiding, zodat een lijst
     * die uit een e-mail of een firewallregel geplakt wordt ook gewoon werkt.
     *
     * @return array<int, string>
     */
    public static function parse(string $raw): array
    {
        return collect(preg_split('/[\s,]+/', $raw) ?: [])
            ->map(fn (string $entry) => trim($entry))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $entries
     * @return array<int, string>
     */
    public static function invalidEntries(array $entries): array
    {
        return array_values(array_filter($entries, fn (string $entry) => ! self::isValidEntry($entry)));
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
     * @param  array<int, string>  $entries
     */
    public static function save(array $entries): void
    {
        Customsetting::set(self::SETTING, implode("\n", array_values(array_unique($entries))), self::siteId());
    }

    public static function clear(): void
    {
        self::save([]);
    }
}

<?php

namespace Dashed\DashedCore\Security;

use Illuminate\Http\Request;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Classes\RateLimits;
use Dashed\DashedCore\Classes\MfaFreshness;
use Dashed\DashedCore\Classes\CmsIdleTimeout;
use Dashed\DashedCore\Classes\CmsIpAllowlist;
use Dashed\DashedCore\Classes\CmsPasswordReset;
use Dashed\DashedCore\Classes\CmsSessionLimits;
use Dashed\DashedCore\Classes\SecurityAlerts;
use Dashed\DashedCore\Classes\TrustedProxies;
use Dashed\DashedCore\Classes\UploadSecurity;
use Dashed\DashedCore\Middleware\TrustedHosts;

/**
 * Eén scherm dat laat zien wat er aan beveiliging aan of uit staat, per
 * installatie. Niet omdat elke regel hier een fout is, maar omdat de
 * uitrolfouten die in de documentatie als valkuil staan (APP_URL op http,
 * een proxy zonder trusted_proxies, debug aan op productie, een beheerder
 * zonder MFA) anders pas opvallen als er iets misgaat.
 *
 * Elk item: sleutel, label, status (ok, warning, danger, info) en een korte
 * uitleg. De volgorde is de leesvolgorde op het scherm.
 */
class SecurityCheck
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const DANGER = 'danger';

    public const INFO = 'info';

    /** Maximale sessieduur voor klanten in minuten (zeven dagen, playbook M2). */
    public const MAX_SESSION_LIFETIME_MINUTES = 10080;

    /** @var array<int, callable(): array<int, array{key: string, label: string, status: string, detail: string}>> */
    protected static array $extensions = [];

    /**
     * Een pakket voegt eigen rijen toe (dashed-ecommerce-core: de pincode voor
     * handmatige betalingen, betaalmethodes met psp own). De closure loopt
     * pas als het scherm gerenderd wordt.
     */
    public static function extend(callable $items): void
    {
        self::$extensions[] = $items;
    }

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public static function run(?Request $request = null): array
    {
        $request ??= request();
        $isLocal = app()->isLocal();

        $items = [];

        $items[] = self::item(
            'app_env',
            __('Omgeving'),
            app()->isProduction() ? self::OK : self::INFO,
            __('APP_ENV staat op :env.', ['env' => (string) app()->environment()])
        );

        $debug = (bool) config('app.debug');
        $items[] = self::item(
            'app_debug',
            __('Debug-modus'),
            $debug ? ($isLocal ? self::INFO : self::DANGER) : self::OK,
            $debug
                ? __('APP_DEBUG staat aan. Buiten een lokale omgeving lekt elke foutpagina configuratie en code.')
                : __('APP_DEBUG staat uit.')
        );

        $appUrl = (string) config('app.url');
        $items[] = self::item(
            'app_url_https',
            __('APP_URL op https'),
            str_starts_with(strtolower($appUrl), 'https://') ? self::OK : ($isLocal ? self::INFO : self::DANGER),
            str_starts_with(strtolower($appUrl), 'https://')
                ? __('APP_URL is :url.', ['url' => $appUrl])
                : __('APP_URL is :url. Ondertekende links (facturen, afmelden, wachtwoord-reset) worden tegen het schema gecontroleerd en zijn dan ongeldig.', ['url' => $appUrl])
        );

        $secure = config('session.secure');
        $items[] = self::item(
            'session_secure',
            __('Sessiecookie alleen over https'),
            $secure === true ? self::OK : ($secure === null ? self::INFO : ($isLocal ? self::INFO : self::WARNING)),
            $secure === true
                ? __('SESSION_SECURE_COOKIE staat aan.')
                : ($secure === null
                    ? __('SESSION_SECURE_COOKIE is niet gezet; Laravel volgt dan het schema van het verzoek.')
                    : __('SESSION_SECURE_COOKIE staat uit; de sessiecookie gaat ook over http mee.'))
        );

        $encrypt = (bool) config('session.encrypt');
        $items[] = self::item(
            'session_encrypt',
            __('Sessie-inhoud versleuteld'),
            $encrypt ? self::OK : self::INFO,
            $encrypt ? __('SESSION_ENCRYPT staat aan.') : __('SESSION_ENCRYPT staat uit. Niet verplicht, wel aan te raden.')
        );

        $behindProxy = $request->headers->has('X-Forwarded-For') || $request->headers->has('CF-Connecting-IP');
        $proxies = TrustedProxies::configured();
        $items[] = self::item(
            'trusted_proxies',
            __('Vertrouwde proxy\'s'),
            $proxies ? self::OK : ($behindProxy ? self::DANGER : self::INFO),
            $proxies
                ? __('DASHED_TRUSTED_PROXIES: :proxies. Dit verzoek komt van :ip.', ['proxies' => implode(', ', $proxies), 'ip' => (string) $request->ip()])
                : ($behindProxy
                    ? __('Dit verzoek draagt een X-Forwarded-For-header maar er is geen proxy vertrouwd: de applicatie ziet :ip, het adres van de proxy. De IP-lijst, de verzoeklimieten en de beveiligingsmeldingen werken dan niet zoals bedoeld. Zet DASHED_TRUSTED_PROXIES.', ['ip' => (string) $request->ip()])
                    : __('Geen proxy geconfigureerd en dit verzoek komt rechtstreeks binnen (:ip).', ['ip' => (string) $request->ip()]))
        );

        $enforce = config('dashed-core.trusted_hosts.enforce');
        $hostsOn = ($enforce === null || $enforce === '') ? ! $isLocal : filter_var($enforce, FILTER_VALIDATE_BOOL);
        $items[] = self::item(
            'trusted_hosts',
            __('Vertrouwde hosts'),
            $hostsOn ? self::OK : ($isLocal ? self::INFO : self::DANGER),
            $hostsOn
                ? __('Alleen deze hosts worden geaccepteerd: :hosts.', ['hosts' => implode(', ', TrustedHosts::allowedHosts())])
                : __('De controle op de Host-header staat uit (DASHED_TRUSTED_HOSTS_ENFORCE). Wie een reset aanvraagt bepaalt dan het domein in de mail.')
        );

        $items[] = self::item(
            'ip_allowlist',
            __('IP-beperking op het CMS'),
            CmsIpAllowlist::isActive() ? self::OK : self::INFO,
            CmsIpAllowlist::isActive()
                ? __(':aantal adres(sen) toegestaan.', ['aantal' => count(CmsIpAllowlist::entries())])
                : __('Geen beperking: het CMS is vanaf elk adres bereikbaar. In te stellen bij Instellingen, Beveiliging.')
        );

        $items[] = self::item(
            'idle_timeout',
            __('Automatisch uitloggen'),
            CmsIdleTimeout::isEnabled() ? self::OK : self::WARNING,
            CmsIdleTimeout::isEnabled()
                ? __('Na :minuten minuten zonder activiteit.', ['minuten' => CmsIdleTimeout::minutes()])
                : __('Uitgeschakeld: een open sessie blijft onbeperkt geldig.')
        );

        $items[] = self::item(
            'session_max',
            __('Sessie hoe dan ook verlopen'),
            CmsSessionLimits::maxIsEnabled() ? self::OK : self::WARNING,
            CmsSessionLimits::maxIsEnabled()
                ? __('Na :minuten minuten opnieuw inloggen, ook bij activiteit. Een login via het onthoud-mij-cookie wordt voor beheerders geweigerd.', ['minuten' => CmsSessionLimits::maxMinutes()])
                : __('Uitgeschakeld: wie actief blijft hoeft nooit opnieuw in te loggen.')
        );

        $items[] = self::item(
            'mfa_reverify',
            __('MFA opnieuw bevestigen'),
            MfaFreshness::hours() > 0 ? self::OK : self::WARNING,
            (MfaFreshness::hours() > 0
                ? __('Elke :uren uur.', ['uren' => MfaFreshness::hours()])
                : __('Alleen bij het inloggen.'))
            . ' ' . (MfaFreshness::bindsIp() ? __('Ook bij een IP-wissel.') : __('Niet bij een IP-wissel.'))
        );

        $withoutMfa = User::query()
            ->where(fn ($query) => $query->whereIn('role', ['superadmin', 'admin'])->orWhereHas('roles'))
            ->where(fn ($query) => $query->whereNull('app_authentication_secret')->where(fn ($q) => $q->whereNull('has_email_authentication')->orWhere('has_email_authentication', false)))
            ->pluck('email')
            ->all();
        $items[] = self::item(
            'mfa_users',
            __('Beheerders met MFA'),
            $withoutMfa ? self::WARNING : self::OK,
            $withoutMfa
                ? __('Nog geen MFA ingesteld (krijgen de instelpagina bij hun volgende bezoek): :emails', ['emails' => implode(', ', $withoutMfa)])
                : __('Elke beheerder heeft MFA ingesteld.')
        );

        $recipients = SecurityAlerts::enabled() ? SecurityAlerts::recipients() : [];
        $items[] = self::item(
            'security_alerts',
            __('Beveiligingsmeldingen'),
            SecurityAlerts::enabled() && $recipients ? self::OK : self::WARNING,
            SecurityAlerts::enabled()
                ? ($recipients
                    ? __('Naar :emails.', ['emails' => implode(', ', $recipients)])
                    : __('Aan, maar er is geen ontvanger: geen ingestelde adressen en geen superadmin met e-mailadres.'))
                : __('Uitgeschakeld.')
        );

        $off = array_keys(array_filter(RateLimits::LIMITERS, fn ($_, $name) => ! RateLimits::isEnabled($name), ARRAY_FILTER_USE_BOTH));
        $items[] = self::item(
            'rate_limits',
            __('Verzoeklimieten'),
            $off ? self::WARNING : self::OK,
            $off
                ? __('Uitgeschakeld (0): :limiters.', ['limiters' => implode(', ', $off)])
                : __('Alle limieten staan aan.')
        );

        $headersOn = filter_var(config('dashed-core.security_headers.enabled', true), FILTER_VALIDATE_BOOL);
        $items[] = self::item(
            'security_headers',
            __('Beveiligingsheaders'),
            $headersOn ? self::OK : self::WARNING,
            $headersOn ? __('HSTS, X-Frame-Options, nosniff, Referrer-Policy en Permissions-Policy worden gezet.') : __('Uitgeschakeld (DASHED_SECURITY_HEADERS).')
        );

        $uncompromised = app()->isProduction() && filter_var(config('dashed-core.passwords.uncompromised', true), FILTER_VALIDATE_BOOL);
        $items[] = self::item(
            'passwords',
            __('Wachtwoordeisen'),
            $uncompromised ? self::OK : self::INFO,
            __('Minimaal :min tekens.', ['min' => (int) config('dashed-core.passwords.min_length', 8)])
            . ' ' . ($uncompromised ? __('Gelekte wachtwoorden worden geweigerd.') : __('De controle op gelekte wachtwoorden geldt alleen in productie.'))
        );

        $uploadDisk = (string) config('livewire.temporary_file_upload.disk');
        $uploadsPrivate = UploadSecurity::temporaryDiskIsPrivate();
        $uploadsGuarded = UploadSecurity::guardIsActive();
        $items[] = self::item(
            'uploads',
            __('Uploads'),
            $uploadsPrivate && $uploadsGuarded ? self::OK : self::DANGER,
            $uploadsPrivate && $uploadsGuarded
                ? __('Tijdelijke uploads staan op de prive disk :disk en elk bestand gaat langs de wachter tegen scripts.', ['disk' => $uploadDisk])
                : trim(($uploadsPrivate ? '' : __('De tijdelijke uploaddisk (:disk) is via de webserver bereikbaar; een nog niet gevalideerd bestand staat dan open op een raadbare URL.', ['disk' => $uploadDisk ?: 'standaard']) . ' ')
                    . ($uploadsGuarded ? '' : __('De wachter tegen scripts ontbreekt op de uploadregels (config/livewire.php overschrijft ze na de boot).')))
        );

        $adminReset = CmsPasswordReset::adminResetEnabled();
        $items[] = self::item(
            'admin_password_reset',
            __('Wachtwoord-reset voor beheerders'),
            $adminReset ? self::WARNING : self::OK,
            $adminReset
                ? __('Aan: een beheerder kan zijn wachtwoord per mail resetten, dus wie bij die mailbox kan neemt het account over. Uit te zetten bij Instellingen, Beveiliging; elk verzoek geeft sowieso een beveiligingsmelding.')
                : __('Uit: beheerders resetten via een collega-superadmin of dashed:set-password. Elk verzoek geeft een beveiligingsmelding.')
        );

        $lifetime = (int) config('session.lifetime');
        $items[] = self::item(
            'session_lifetime',
            __('Sessieduur'),
            $lifetime > 0 && $lifetime <= self::MAX_SESSION_LIFETIME_MINUTES ? self::OK : self::WARNING,
            $lifetime > 0 && $lifetime <= self::MAX_SESSION_LIFETIME_MINUTES
                ? __('SESSION_LIFETIME is :minuten minuten.', ['minuten' => $lifetime])
                : __('SESSION_LIFETIME is :minuten minuten; een gestolen cookie blijft zo lang bruikbaar. Hooguit :max (zeven dagen).', ['minuten' => $lifetime, 'max' => self::MAX_SESSION_LIFETIME_MINUTES])
        );

        $httpOnly = (bool) config('session.http_only', true);
        $sameSite = (string) config('session.same_site');
        $items[] = self::item(
            'session_cookie_flags',
            __('Sessiecookie-vlaggen'),
            $httpOnly && in_array($sameSite, ['lax', 'strict'], true) ? self::OK : self::WARNING,
            $httpOnly && in_array($sameSite, ['lax', 'strict'], true)
                ? __('http_only aan, same_site :same_site.', ['same_site' => $sameSite])
                : trim(($httpOnly ? '' : __('http_only staat uit: JavaScript kan de sessiecookie lezen.') . ' ') . (in_array($sameSite, ['lax', 'strict'], true) ? '' : __('same_site is :same_site; lax of strict houdt het cookie weg uit verzoeken van andere sites.', ['same_site' => $sameSite ?: 'leeg'])))
        );

        $secrets = ProjectScan::secretsInConfig(config_path());
        $items[] = self::item(
            'config_secrets',
            __('Geheimen in config/'),
            $secrets === [] ? self::OK : self::DANGER,
            $secrets === []
                ? __('Geen env()-aanroep met een echte waarde als terugval.')
                : __('Deze staan met een echte waarde als terugval in git en gelden als gelekt; verplaats ze naar .env en roteer ze: :lijst', ['lijst' => implode(', ', $secrets)])
        );

        $strayPhp = ProjectScan::strayPhpInPublic(public_path());
        $items[] = self::item(
            'public_php',
            __('PHP-bestanden in public/'),
            $strayPhp === [] ? self::OK : self::DANGER,
            $strayPhp === []
                ? __('Alleen index.php.')
                : __('Naast index.php staan er PHP-bestanden die de webserver uitvoert: :lijst', ['lijst' => implode(', ', array_slice($strayPhp, 0, 10))])
        );

        $shell = ProjectScan::shellFunctions([app_path(), base_path('routes')]);
        $items[] = self::item(
            'shell_functions',
            __('Shell-functies in code'),
            $shell === [] ? self::OK : self::DANGER,
            $shell === []
                ? __('Geen aanroep van shell_exec, exec, system, passthru, proc_open of popen in app/ en routes/.')
                : __('Aanroepen van shell-functies in de eigen code (een backdoor ziet er precies zo uit): :lijst', ['lijst' => implode(', ', array_slice($shell, 0, 10))])
        );

        if (config()->has('sanctum')) {
            $expiration = config('sanctum.expiration');
            $items[] = self::item(
                'sanctum_expiration',
                __('API-tokens verlopen'),
                $expiration ? self::OK : self::WARNING,
                $expiration
                    ? __('Sanctum-tokens verlopen na :minuten minuten.', ['minuten' => (int) $expiration])
                    : __('Sanctum-tokens verlopen nooit (sanctum.expiration is leeg). Zet bijvoorbeeld 525600 (een jaar).')
            );
        }

        foreach (self::$extensions as $extension) {
            foreach ((array) $extension() as $item) {
                $items[] = $item;
            }
        }

        $staticRobots = file_exists(public_path('robots.txt'));
        $items[] = self::item(
            'robots',
            __('robots.txt'),
            $staticRobots ? self::WARNING : self::OK,
            $staticRobots
                ? __('Er staat een statisch public/robots.txt; dat gaat voor de dynamische versie die het CMS en de bestelroutes uitsluit.')
                : __('Dynamisch: CMS, Horizon, facturen en bestelroutes zijn uitgesloten.')
        );

        return $items;
    }

    /**
     * @return array<string, int>
     */
    public static function counts(array $items): array
    {
        $counts = [self::OK => 0, self::WARNING => 0, self::DANGER => 0, self::INFO => 0];

        foreach ($items as $item) {
            $counts[$item['status']] = ($counts[$item['status']] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    protected static function item(string $key, string $label, string $status, string $detail): array
    {
        return compact('key', 'label', 'status', 'detail');
    }
}

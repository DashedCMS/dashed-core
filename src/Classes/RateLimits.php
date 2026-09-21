<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Support\Facades\RateLimiter;

/**
 * De benoemde verzoeklimieten van de front-end, per IP-adres per minuut.
 *
 * Elke limiter heeft een instelling op de eerste site (dezelfde keuze als
 * [[CmsIpAllowlist]]: dit is een eigenschap van de installatie, niet van een
 * site) met een standaard; 0 zet hem uit. De routes hangen eraan via
 * `throttle:<naam>`, en de limiter leest de instelling per verzoek, dus een
 * wijziging in het scherm werkt meteen.
 *
 * De limieten tellen op $request->ip(). Achter Cloudflare of een load balancer
 * is dat het adres van de proxy zolang dashed-core.trusted_proxies leeg is, en
 * dan delen alle bezoekers samen een limiet.
 */
class RateLimits
{
    /**
     * @var array<string, array{setting: string, default: int}>
     */
    public const LIMITERS = [
        // Pagina's en downloads die op een orderhash werken: factuur, pakbon,
        // proforma, restbetaling, retourstatus. Tegen het raden van hashes.
        'dashed-order-pages' => ['setting' => 'rate_limit_order_pages', 'default' => 20],
        // Toevoegen aan, bijwerken en legen van de winkelwagen.
        'dashed-cart' => ['setting' => 'rate_limit_cart', 'default' => 60],
        // Een kortingscode invoeren. Tegen het raden van codes.
        'dashed-discount-code' => ['setting' => 'rate_limit_discount_code', 'default' => 10],
        // Inloggen, registreren en wachtwoord vergeten op de website.
        'dashed-frontend-auth' => ['setting' => 'rate_limit_frontend_auth', 'default' => 10],
    ];

    /**
     * Limiters die een pakket zelf aanmeldt. Statisch, net als LIMITERS; een
     * pakket meldt ze bij elke boot opnieuw aan onder dezelfde naam.
     *
     * @var array<string, array{setting: string, default: int, label: string, help: ?string, by: string}>
     */
    private static array $extended = [];

    public static function extend(string $name, string $setting, int $default, string $label, ?string $help = null, string $by = 'ip'): void
    {
        self::$extended[$name] = [
            'setting' => $setting,
            'default' => $default,
            'label' => $label,
            'help' => $help,
            'by' => $by,
        ];

        self::define($name);
    }

    public static function forgetExtended(string $name): void
    {
        unset(self::$extended[$name]);
    }

    public static function all(): array
    {
        return array_merge(self::LIMITERS, self::$extended);
    }

    public static function extended(): array
    {
        return self::$extended;
    }

    public static function setting(string $name): string
    {
        return self::all()[$name]['setting'];
    }

    public static function default(string $name): int
    {
        return self::all()[$name]['default'];
    }

    public static function perMinute(string $name): int
    {
        $value = Customsetting::get(self::setting($name), (string) Sites::getFirstSite()['id'], (string) self::default($name));

        if ($value === null || $value === '') {
            return self::default($name);
        }

        return max(0, (int) $value);
    }

    public static function isEnabled(string $name): bool
    {
        return self::perMinute($name) > 0;
    }

    /**
     * Meldt elke limiter aan. Hoort in de boot-fase; de closure leest de
     * instelling pas als het verzoek binnenkomt.
     */
    public static function register(): void
    {
        foreach (array_keys(self::LIMITERS) as $name) {
            self::define($name);
        }
    }

    private static function define(string $name): void
    {
        RateLimiter::for($name, function (Request $request) use ($name) {
            $perMinute = self::perMinute($name);

            if ($perMinute <= 0) {
                return Limit::none();
            }

            return Limit::perMinute($perMinute)->by($name . '|' . self::keyFor($name, $request));
        });
    }

    /**
     * Een API-sleutel telt per sleutel: meerdere afnemers achter dezelfde
     * koppelpartij delen anders één IP en dus één limiet.
     */
    private static function keyFor(string $name, Request $request): string
    {
        if ((self::all()[$name]['by'] ?? 'ip') === 'token') {
            $token = $request->user()?->currentAccessToken();

            if ($token && method_exists($token, 'getKey') && $token->getKey()) {
                return 'token:' . $token->getKey();
            }
        }

        return $request->ip() ?: 'unknown';
    }

    /**
     * Voor code die zelf telt (Livewire-componenten, waar geen routemiddleware
     * tussen zit). Geeft het aantal seconden tot de volgende poging, of null
     * als het verzoek door mag; telt het verzoek dan meteen mee.
     */
    public static function hit(string $name, string $key): ?int
    {
        $perMinute = self::perMinute($name);

        if ($perMinute <= 0) {
            return null;
        }

        $throttleKey = $name . '|' . $key;

        if (RateLimiter::tooManyAttempts($throttleKey, $perMinute)) {
            return RateLimiter::availableIn($throttleKey);
        }

        RateLimiter::hit($throttleKey, 60);

        return null;
    }
}

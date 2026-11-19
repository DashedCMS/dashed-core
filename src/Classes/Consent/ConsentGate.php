<?php

namespace Dashed\DashedCore\Classes\Consent;

use Throwable;

/**
 * De ene plek waar de front-end vraagt of een script op cookietoestemming
 * moet wachten. Zonder gebonden ConsentProvider, of met de provider uit voor
 * deze site, verandert er niets: alles laadt zoals altijd.
 *
 * Een script dat wacht krijgt type="text/plain" en data-dashed-consent met
 * de categorieën waarvan er één moet zijn toegestaan. De lader van de
 * provider zet het om zodra die toestemming er is. Dat gebeurt in de
 * browser, zodat de HTML voor elke bezoeker gelijk is en de responscache
 * gewoon blijft werken.
 */
class ConsentGate
{
    public const CATEGORIES = ['necessary', 'preferences', 'analytics', 'marketing'];

    public static function provider(): ?ConsentProvider
    {
        if (! app()->bound(ConsentProvider::class)) {
            return null;
        }

        try {
            return app(ConsentProvider::class);
        } catch (Throwable) {
            return null;
        }
    }

    public static function active(): bool
    {
        $provider = static::provider();

        if (! $provider) {
            return false;
        }

        try {
            return $provider->active();
        } catch (Throwable) {
            // Een kapotte provider mag de site niet platleggen; zonder
            // toestemmingsbeheer laadt alles zoals voorheen.
            return false;
        }
    }

    public static function headStart(): string
    {
        return static::active() ? static::provider()->headStart() : '';
    }

    public static function bodyEnd(): string
    {
        return static::active() ? static::provider()->bodyEnd() : '';
    }

    /**
     * Attributen voor een <script>-tag die op toestemming moet wachten.
     *
     * @param  array<int, string>|string  $categories  één van deze moet zijn toegestaan
     * @param  bool  $google  een Google-tag, die in de geavanceerde Consent Mode meteen mag laden
     * @param  bool  $delayed  het script staat ook al op type="text/plain" vanwege "Trackingscripts uitstellen"
     */
    public static function scriptAttributes(array|string $categories, bool $google = false, bool $delayed = false): string
    {
        if (! static::active()) {
            return $delayed ? 'type="text/plain" data-dashed-delayed' : '';
        }

        if ($google && static::provider()->loadsGoogleWithoutConsent()) {
            return $delayed ? 'type="text/plain" data-dashed-delayed' : '';
        }

        $categories = implode(' ', array_filter((array) $categories));

        return 'type="text/plain"'.($delayed ? ' data-dashed-delayed' : '').' data-dashed-consent="'.e($categories).'"';
    }

    /**
     * Losse HTML met scripts (extra scripts, scripts per pagina).
     */
    public static function rewrite(?string $html): string
    {
        $html = (string) $html;

        if ($html === '' || ! static::active()) {
            return $html;
        }

        return static::provider()->rewrite($html);
    }

    /**
     * Zonder toestemmingsbeheer is alles toegestaan, net als voorheen.
     */
    public static function allows(string $category): bool
    {
        if ($category === 'necessary' || ! static::active()) {
            return true;
        }

        try {
            return static::provider()->allows($category);
        } catch (Throwable) {
            return false;
        }
    }
}

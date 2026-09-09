<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Http\Middleware\TrustProxies;

/**
 * Zet de vertrouwde proxy's uit dashed-core.trusted_proxies op Laravels eigen
 * TrustProxies-middleware, die in de globale middleware van elk project zit
 * (ook in de oude Kernel-opzet: `at()` schrijft een statische eigenschap van
 * de basisklasse). Zonder configuratie blijft alles zoals het was.
 *
 * Waarom een eigen variabele in plaats van bootstrap/app.php per project:
 * één van de zestig projecten had hem gezet, en elke maatregel die op het
 * IP-adres van de bezoeker leunt (de IP-lijst van het CMS, de
 * verzoeklimieten, de beveiligingsmeldingen, MFA bij IP-wissel) is achter
 * Cloudflare waardeloos zolang de applicatie het adres van de proxy ziet.
 */
class TrustedProxies
{
    /**
     * @return array<int, string>
     */
    public static function configured(): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            (array) config('dashed-core.trusted_proxies', []),
        )));
    }

    public static function apply(): void
    {
        $proxies = self::configured();

        if ($proxies === []) {
            return;
        }

        TrustProxies::at(in_array('*', $proxies, true) || in_array('**', $proxies, true) ? '*' : $proxies);
    }
}

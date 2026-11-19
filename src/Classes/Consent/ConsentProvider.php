<?php

namespace Dashed\DashedCore\Classes\Consent;

/**
 * Een pakket dat cookietoestemming regelt (dashed-cookies) bindt deze
 * interface in de container. dashed-core kent dat pakket niet: de head en
 * de body vragen alleen via ConsentGate of en hoe een script moet wachten.
 */
interface ConsentProvider
{
    /**
     * Staat het toestemmingsbeheer aan voor de huidige site?
     */
    public function active(): bool;

    /**
     * HTML die als allereerste in de head moet staan, vóór elk trackingscript
     * (de standaardstand van Google Consent Mode en de lader).
     */
    public function headStart(): string;

    /**
     * HTML aan het eind van de body (de banner).
     */
    public function bodyEnd(): string;

    /**
     * Mag een Google-tag (GTM of gtag.js) meteen laden, omdat Consent Mode
     * in de geavanceerde stand staat en de tag zelf de toestemming volgt?
     */
    public function loadsGoogleWithoutConsent(): bool;

    /**
     * Herschrijft losse HTML (extra scripts uit de instellingen of een
     * pagina) zodat elk script op toestemming wacht.
     */
    public function rewrite(string $html): string;

    /**
     * Heeft de bezoeker van dit verzoek toestemming gegeven voor deze
     * categorie? Voor server-side events (Conversions API en dergelijke).
     */
    public function allows(string $category): bool;
}

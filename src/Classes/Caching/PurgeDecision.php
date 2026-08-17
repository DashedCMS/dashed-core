<?php

namespace Dashed\DashedCore\Classes\Caching;

/**
 * Bepaalt of een gewijzigde instelling de edge-cache hoort te legen.
 *
 * Een deel van de Customsettings beschrijft niet wat de site toont maar hoe hij
 * draait: hartslagen van de integratiechecks, de laatste foutmelding van een
 * betaalprovider, het lopende factuurnummer. Die worden bij elke probe, elke
 * bestelling of elke betaling weggeschreven en landen op geen enkele pagina.
 *
 * Ze hingen wel aan de saved-hook van Customsetting, en die dispatcht een
 * volledige Cloudflare zone-purge per site. Daardoor purgede de zone in de
 * praktijk om de paar seconden zonder dat er iets aan de inhoud veranderde.
 *
 * Bewust een denylist en geen allowlist: een onbekende instelling purgt liever
 * te veel dan te weinig, want te weinig purgen serveert stilletjes verouderde
 * content en dat is het ergere van de twee kwaden.
 */
class PurgeDecision
{
    /**
     * Patronen in fnmatch-vorm. Ze worden tegen de volledige naam gematcht, dus
     * 'invoice_footer_text' blijft inhoud ook al lijkt hij op de factuurteller.
     *
     * @var list<string>
     */
    public const OPERATIONAL_SETTING_PATTERNS = [
        // Hartslagen en foutmeldingen van IntegrationHealthRunner: twee
        // schrijfacties per probe, per integratie, per site.
        'integration_*_last_success_at',
        'integration_*_last_error',

        // Verbindingsstatus van betaalproviders, geschreven bij elke check.
        '*_connection_error',
        '*_connected',

        // Tellers die per bestelling of per betaling opschuiven.
        'current_invoice_number',
        'cash_register_amount',
    ];

    public static function isOperationalSetting(string $name): bool
    {
        foreach (self::OPERATIONAL_SETTING_PATTERNS as $pattern) {
            if (fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }
}

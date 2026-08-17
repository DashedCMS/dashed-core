<?php

declare(strict_types=1);

use Dashed\DashedCore\Classes\Caching\PurgeDecision;

/**
 * Operationele instellingen leggen vast hoe de site draait, niet wat hij toont.
 * Ze staan op geen enkele pagina, dus een wijziging hoeft de edge-cache niet te
 * legen. Ze werden voorheen wel gepurged, en omdat sommige bij elke health-probe
 * of bestelling worden weggeschreven, vuurde daardoor de Cloudflare-purge continu.
 */
it('herkent de heartbeats van de integratiechecks als operationeel', function () {
    expect(PurgeDecision::isOperationalSetting('integration_mollie_last_success_at'))->toBeTrue()
        ->and(PurgeDecision::isOperationalSetting('integration_multisafepay_last_success_at'))->toBeTrue()
        ->and(PurgeDecision::isOperationalSetting('integration_mollie_last_error'))->toBeTrue();
});

it('herkent verbindingsfouten van betaalproviders als operationeel', function () {
    expect(PurgeDecision::isOperationalSetting('fiserv_connection_error'))->toBeTrue()
        ->and(PurgeDecision::isOperationalSetting('mollie_connection_error'))->toBeTrue();
});

it('herkent boekhoudkundige tellers als operationeel', function () {
    expect(PurgeDecision::isOperationalSetting('current_invoice_number'))->toBeTrue()
        ->and(PurgeDecision::isOperationalSetting('cash_register_amount'))->toBeTrue();
});

/**
 * De keerzijde is belangrijker dan de lijst zelf: een instelling die wél op de
 * pagina landt, moet blijven purgen. Anders serveert de edge stille verouderde
 * content en is het middel erger dan de kwaal.
 */
it('behandelt instellingen die op de pagina landen als inhoudelijk', function () {
    foreach ([
        'site_name',
        'site_logo',
        'mobile_site_logo',
        'usps',
        'default_meta_data_image',
        'webmaster_tags',
    ] as $name) {
        expect(PurgeDecision::isOperationalSetting($name))->toBeFalse("'{$name}' hoort wel te purgen");
    }
});

it('laat een onbekende instelling voor de zekerheid purgen', function () {
    expect(PurgeDecision::isOperationalSetting('een_nieuwe_instelling_die_nog_niet_bestaat'))->toBeFalse();
});

/**
 * De patronen mogen niet te gulzig zijn: 'invoice_footer_text' staat wél op de
 * factuur en lijkt op de teller, maar is inhoud.
 */
it('kapt niet te breed op de naam van een teller', function () {
    expect(PurgeDecision::isOperationalSetting('invoice_footer_text'))->toBeFalse()
        ->and(PurgeDecision::isOperationalSetting('invoice_number_prefix'))->toBeFalse();
});

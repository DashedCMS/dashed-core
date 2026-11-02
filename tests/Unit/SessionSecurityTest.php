<?php

declare(strict_types=1);

use Dashed\DashedCore\Classes\SessionSecurity;

/**
 * Lovora (2-11-2026): SESSION_LIFETIME stond op 1.200.000 minuten en Sanctum-tokens verliepen nooit. Een week
 * is het maximum, een jaar de standaard voor tokens; een kortere waarde van een project blijft staan.
 */
it('caps the session lifetime at one week and keeps shorter values', function () {
    expect(SessionSecurity::cappedLifetime(1200000))->toBe(10080)
        ->and(SessionSecurity::cappedLifetime('120'))->toBe(120)
        ->and(SessionSecurity::cappedLifetime(10080))->toBe(10080)
        ->and(SessionSecurity::cappedLifetime(null))->toBe(10080)
        ->and(SessionSecurity::cappedLifetime(0))->toBe(10080);
});

it('lets sanctum tokens expire after a year unless the project set its own duration', function () {
    expect(SessionSecurity::sanctumExpiration(null))->toBe(525600)
        ->and(SessionSecurity::sanctumExpiration(''))->toBe(525600)
        ->and(SessionSecurity::sanctumExpiration(43200))->toBe(43200)
        ->and(SessionSecurity::sanctumExpiration('60'))->toBe(60);
});

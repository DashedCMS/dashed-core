<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Security\SecurityCheck;

/**
 * Sessie- en tokenduur, afgedwongen vanuit het pakket voor elk project (Robin, 2 november 2026), zoals
 * UploadSecurity dat voor uploads doet.
 *
 * Een sessie leeft hoogstens een week (SecurityCheck::MAX_SESSION_LIFETIME_MINUTES): een gestolen
 * sessiecookie is dan na zeven dagen waardeloos. Projecten hadden hier waarden tot 1.200.000 minuten (2,3
 * jaar) staan. Een kortere waarde van een project blijft staan.
 *
 * Sanctum-tokens (API, printers, mobiele app) verlopen standaard na een jaar als het project zelf niets
 * heeft gezet. Een project dat bewust een andere duur kiest, publiceert config/sanctum.php met die waarde.
 */
class SessionSecurity
{
    public const SANCTUM_DEFAULT_MINUTES = 525600;

    public static function apply(): void
    {
        config(['session.lifetime' => static::cappedLifetime(config('session.lifetime'))]);

        if (config()->has('sanctum')) {
            config(['sanctum.expiration' => static::sanctumExpiration(config('sanctum.expiration'))]);
        }
    }

    public static function cappedLifetime(mixed $lifetime): int
    {
        $lifetime = (int) $lifetime;

        if ($lifetime <= 0) {
            return SecurityCheck::MAX_SESSION_LIFETIME_MINUTES;
        }

        return min($lifetime, SecurityCheck::MAX_SESSION_LIFETIME_MINUTES);
    }

    public static function sanctumExpiration(mixed $expiration): int
    {
        return is_numeric($expiration) && (int) $expiration > 0 ? (int) $expiration : static::SANCTUM_DEFAULT_MINUTES;
    }
}

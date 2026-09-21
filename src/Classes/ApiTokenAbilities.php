<?php

namespace Dashed\DashedCore\Classes;

/**
 * Sanctum-abilities die meer dan één pakket moet kennen. De mobiele API en de
 * afnemers-API gebruiken dezelfde tokentabel; deze constante is de grens.
 * Toets altijd op de letterlijke ability: tokenCan() laat een '*'-token ook
 * door.
 */
final class ApiTokenAbilities
{
    public const RESELLER = 'reseller';

    public static function isReseller(?object $token): bool
    {
        if ($token === null) {
            return false;
        }

        return in_array(self::RESELLER, (array) ($token->abilities ?? []), true);
    }
}

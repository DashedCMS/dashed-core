<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Security;

use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

/**
 * Zolang App\Models\User in een klantproject $casts overschreef, schreef
 * Filament het MFA-geheim en de (gehashte) herstelcodes onversleuteld weg.
 * Dit zet die waarden op hun plek om naar de vorm van de encrypted-casts,
 * zodat de gebruiker zijn authenticator-app gewoon houdt.
 *
 * Een waarde die er als versleuteld uitziet blijft altijd staan, ook als hij
 * met de huidige sleutel niet te openen is: dan is de sleutel gewisseld, en
 * opnieuw versleutelen zou het oorspronkelijke geheim alleen verder begraven.
 */
class MfaSecretEncryption
{
    /** @return int het aantal bijgewerkte gebruikers */
    public static function encryptPlaintext(): int
    {
        [$secretColumn, $codesColumn] = User::MFA_SECRET_COLUMNS;
        $updated = 0;

        DB::table('users')
            ->where(fn ($query) => $query->whereNotNull($secretColumn)->orWhereNotNull($codesColumn))
            ->select(['id', $secretColumn, $codesColumn])
            ->chunkById(200, function ($rows) use ($secretColumn, $codesColumn, &$updated): void {
                foreach ($rows as $row) {
                    $changes = array_filter([
                        $secretColumn => self::encryptedSecret($row->{$secretColumn}),
                        $codesColumn => self::encryptedCodes($row->{$codesColumn}),
                    ], fn ($change) => $change !== false);

                    if ($changes === []) {
                        continue;
                    }

                    DB::table('users')->where('id', $row->id)->update($changes);
                    $updated++;
                }
            });

        return $updated;
    }

    /** @return string|null|false false = ongewijzigd laten */
    private static function encryptedSecret(?string $value): string|null|false
    {
        if ($value === null || self::looksEncrypted($value)) {
            return false;
        }

        return $value === '' ? null : Crypt::encryptString($value);
    }

    /** @return string|null|false false = ongewijzigd laten */
    private static function encryptedCodes(?string $value): string|null|false
    {
        if ($value === null || self::looksEncrypted($value)) {
            return false;
        }

        if ($value === '') {
            return null;
        }

        // Onversleuteld schreef Eloquent de array als JSON weg; de cast
        // encrypted:array versleutelt precies die JSON.
        return is_array(json_decode($value, true)) ? Crypt::encryptString($value) : false;
    }

    private static function looksEncrypted(string $value): bool
    {
        $payload = json_decode((string) base64_decode($value, true), true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}

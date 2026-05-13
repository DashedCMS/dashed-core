<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Session;

/**
 * Centrale captura voor e-mailadressen. Zodra een bezoeker ergens een
 * e-mail invult (checkout, contactformulier, popup, nieuwsbrief, etc.)
 * roept de betreffende code `EmailCapture::capture($email, $source)` aan.
 * Het adres + bron + tijdstip staat dan in de sessie en is via de
 * `captured_email()` helper of `$capturedEmail` blade-variabele
 * beschikbaar in alle views - handig om het veld bij volgend bezoek
 * voor te vullen of personalisatie te tonen.
 */
class EmailCapture
{
    public const SESSION_KEY_EMAIL = '_dashed_captured_email';
    public const SESSION_KEY_SOURCE = '_dashed_captured_email_source';
    public const SESSION_KEY_AT = '_dashed_captured_email_at';

    public static function capture(?string $email, ?string $source = null): void
    {
        $email = is_string($email) ? trim($email) : '';
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $email = strtolower($email);

        // Overschrijf alleen als het adres écht verandert; bij hetzelfde
        // adres updaten we niet om onnodige session-writes te voorkomen.
        if (Session::get(self::SESSION_KEY_EMAIL) === $email) {
            return;
        }

        Session::put(self::SESSION_KEY_EMAIL, $email);
        Session::put(self::SESSION_KEY_SOURCE, $source ?: 'unknown');
        Session::put(self::SESSION_KEY_AT, now()->toIso8601String());
    }

    public static function current(): ?string
    {
        $email = Session::get(self::SESSION_KEY_EMAIL);

        return is_string($email) && $email !== '' ? $email : null;
    }

    public static function source(): ?string
    {
        $source = Session::get(self::SESSION_KEY_SOURCE);

        return is_string($source) && $source !== '' ? $source : null;
    }

    public static function capturedAt(): ?string
    {
        $at = Session::get(self::SESSION_KEY_AT);

        return is_string($at) && $at !== '' ? $at : null;
    }

    public static function forget(): void
    {
        Session::forget([
            self::SESSION_KEY_EMAIL,
            self::SESSION_KEY_SOURCE,
            self::SESSION_KEY_AT,
        ]);
    }
}

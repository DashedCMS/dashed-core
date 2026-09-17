<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Mail\Contracts\ContainsSecrets;

/**
 * Houdt geheimen uit het logboek van verzonden mails. Twee lagen:
 *
 * 1. Mails die per definitie een geheim bevatten (nieuw beheerdersaccount,
 *    wachtwoordreset, MFA-code per mail) krijgen geen body in het logboek,
 *    alleen een vaste tekst. Welke dat zijn staat in de config
 *    `dashed-core.sent_emails.withhold_body_for`, en een eigen mailable
 *    meldt zich aan door ContainsSecrets te implementeren.
 * 2. Elke andere mail wordt gescrubd als vangnet: de waarde van een
 *    mailgegeven met een naam als `password`, `pin` of `code` wordt in de
 *    body gemaskeerd, en tokens en handtekeningen in links worden weggehaald.
 *    Dat vangt een mail die niemand op de lijst heeft gezet.
 */
class SentEmailScrubber
{
    public const WITHHELD = 'Inhoud niet opgeslagen: deze mail bevat een wachtwoord, code of inloglink.';

    public const MASK = '••••••';

    /** Minimale lengte van een waarde om als geheim gemaskeerd te worden; korter geeft te veel valse treffers. */
    protected const MIN_SECRET_LENGTH = 4;

    /**
     * @param  array<string, mixed>  $data  de view-data van de mail (Mailable-properties, of de notificatiedata)
     */
    public static function withholdsBody(array $data): bool
    {
        // Laravel zet de mailable als klassenaam in de data, niet als object.
        $mailable = static::className($data['__laravel_mailable'] ?? null);
        if ($mailable && is_a($mailable, ContainsSecrets::class, true)) {
            return true;
        }

        $classes = array_filter((array) config('dashed-core.sent_emails.withhold_body_for', []));
        foreach ([$mailable, $data['__laravel_notification'] ?? null] as $class) {
            if (! $class || ! is_string($class)) {
                continue;
            }
            foreach ($classes as $withheld) {
                if (is_a($class, $withheld, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Maskeert geheime waarden uit de mailgegevens en tokens in links.
     *
     * @param  array<string, mixed>  $data
     */
    public static function scrub(?string $body, array $data = []): ?string
    {
        if ($body === null || $body === '') {
            return $body;
        }

        foreach (static::secretValues($data) as $value) {
            $body = str_replace([$value, htmlspecialchars($value, ENT_QUOTES), urlencode($value)], self::MASK, $body);
        }

        $params = array_filter((array) config('dashed-core.sent_emails.secret_query_params', []));
        if ($params !== []) {
            $pattern = '/([?&](?:amp;)?(?:' . implode('|', array_map('preg_quote', $params)) . ')=)[^&"\'\s<>#]+/i';
            $body = preg_replace($pattern, '$1' . self::MASK, $body) ?? $body;
        }

        return $body;
    }

    /**
     * De klasse die in het logboek komt: de mailable, of bij een notificatie
     * de notificatieklasse, zodat een MFA-code per mail ook herkenbaar is.
     *
     * @param  array<string, mixed>  $data
     */
    public static function classFor(array $data): ?string
    {
        return static::className($data['__laravel_mailable'] ?? null) ?? ($data['__laravel_notification'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    protected static function secretValues(array $data): array
    {
        $keys = array_map('strtolower', array_filter((array) config('dashed-core.sent_emails.secret_keys', [])));
        $values = [];

        foreach ($data as $key => $value) {
            if (! is_string($key) || ! in_array(strtolower($key), $keys, true)) {
                continue;
            }
            if (is_scalar($value) && mb_strlen((string) $value) >= self::MIN_SECRET_LENGTH) {
                $values[] = (string) $value;
            }
        }

        // Langste eerst, anders laat een korter geheim een deel van een langer staan.
        usort($values, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return array_values(array_unique($values));
    }

    protected static function className(mixed $mailable): ?string
    {
        if (is_object($mailable)) {
            return get_class($mailable);
        }

        return is_string($mailable) && $mailable !== '' ? $mailable : null;
    }
}

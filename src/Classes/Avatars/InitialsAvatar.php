<?php

namespace Dashed\DashedCore\Classes\Avatars;

use Illuminate\Support\Str;

/**
 * Avatar zonder upload: een ronde SVG met de initialen van een gebruiker,
 * geserveerd door /user-avatar/{initials}. Een '-' betekent een neutrale
 * cirkel zonder tekst (geen gebruiker of geen bruikbare letters).
 *
 * Bewust niet onder /dashed (die prefix staat op sommige servers dicht) en
 * zonder .svg-extensie (webservers serveren die soms als statisch bestand).
 */
class InitialsAvatar
{
    public const ROUTE = 'dashed.user-avatar';

    public const PATTERN = '[A-Z0-9]{1,2}|-';

    public const NEUTRAL = '-';

    /**
     * Voorletter voornaam + voorletter van het laatste woord van de
     * achternaam ("van Maasakker" geeft M). Zonder namen: de eerste letter
     * van de terugval (naam of e-mailadres).
     */
    public static function initials(?string $firstName, ?string $lastName, ?string $fallback = null): string
    {
        $first = static::firstChar($firstName);
        $lastWords = preg_split('/\s+/', trim((string) $lastName)) ?: [];
        $last = static::firstChar((string) end($lastWords));

        $initials = $first.$last;

        if ($initials === '') {
            $fallback = trim((string) $fallback);
            $fallback = str_contains($fallback, '@') ? Str::before($fallback, '@') : $fallback;
            $initials = static::firstChar($fallback);
        }

        return mb_substr($initials, 0, 2);
    }

    public static function url(?string $initials): string
    {
        $initials = static::sanitize($initials);

        return route(static::ROUTE, ['initials' => $initials, 'v' => static::version()]);
    }

    public static function sanitize(?string $initials): string
    {
        $initials = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii((string) $initials)));
        $initials = substr($initials, 0, 2);

        return $initials === '' ? static::NEUTRAL : $initials;
    }

    public static function svg(string $initials): string
    {
        $config = static::config();
        $text = $initials === static::NEUTRAL ? '' : e(static::sanitize($initials));
        $fontSize = strlen($text) > 1 ? 40 : 46;

        $label = $text === ''
            ? ''
            : '<text x="50" y="50" dy=".35em" text-anchor="middle" font-family="'.e($config['font_family']).'" font-size="'.$fontSize.'" fill="'.e($config['text']).'">'.$text.'</text>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" role="img"'
            .($text === '' ? ' aria-hidden="true"' : ' aria-label="'.$text.'"').'>'
            .'<circle cx="50" cy="50" r="50" fill="'.e($config['background']).'"/>'
            .$label
            .'</svg>';
    }

    /** Wijzigt als de kleuren wijzigen, zodat lang gecachete SVG's ververst worden. */
    public static function version(): string
    {
        return substr(md5(implode('|', static::config())), 0, 8);
    }

    /** @return array{background: string, text: string, font_family: string} */
    public static function config(): array
    {
        $config = (array) config('dashed-core.avatar', []);

        return [
            'background' => static::color($config['background'] ?? null, '#b4e6ce'),
            'text' => static::color($config['text'] ?? null, '#30545B'),
            'font_family' => (string) ($config['font_family'] ?? '\'DM Serif Display\', Georgia, serif'),
        ];
    }

    protected static function color(mixed $value, string $default): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value) ? $value : $default;
    }

    protected static function firstChar(?string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii(trim((string) $value)));

        return strtoupper(substr((string) $value, 0, 1));
    }
}

<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Str;

/**
 * Kliks en opens van uitgaande mails meten op het eigen domein.
 *
 * De bestemming staat ondertekend in de link zelf en niet in een tabel. Zo
 * blijft een knop in een oude mail werken nadat het logboek is opgeruimd, en
 * komen links met tokens niet in een extra tabel terecht. De handtekening maakt
 * dat de route geen open redirect is.
 */
class MailTracking
{
    public const MODE_OWN = 'eigen';

    public const MODE_POSTMARK = 'postmark';

    public const MODE_OFF = 'uit';

    public const ID_HEADER = 'X-Dashed-Mail-Id';

    /** Afmeldlinks gaan rechtstreeks: een scanner mag daar geen klik op tellen. */
    protected const SKIP_PATTERN = '/unsubscribe|afmeld|abmeld/i';

    protected const ANCHOR_PATTERN = '/(<a\b[^>]*?\shref\s*=\s*)(["\'])(.*?)\2/is';

    public static function mode(): string
    {
        if (! config('dashed-core.sent_emails.track_opens_clicks', true)) {
            return self::MODE_OFF;
        }

        return (string) config('dashed-core.sent_emails.tracking', self::MODE_OWN);
    }

    public static function newId(): string
    {
        return Str::random(32);
    }

    public static function apply(string $html, string $id): string
    {
        $html = self::replaceAnchors($html, function (string $url) use ($id): ?string {
            return self::shouldRewrite($url) ? self::clickUrl($id, $url) : null;
        });

        $pixel = self::pixelTag($id);
        $position = strripos($html, '</body>');

        return $position === false
            ? $html . $pixel
            : substr_replace($html, $pixel, $position, 0);
    }

    /** Geeft de mail zoals hij was vóór apply(), voor het logboek in het CMS. */
    public static function strip(string $html, string $id): string
    {
        $prefix = route('dashed.frontend.mail.click', ['id' => $id]) . '?';

        $html = self::replaceAnchors($html, function (string $url) use ($id, $prefix): ?string {
            if (! str_starts_with($url, $prefix)) {
                return null;
            }

            parse_str(substr($url, strlen($prefix)), $query);

            return self::destination($id, (string) ($query['u'] ?? ''), (string) ($query['s'] ?? ''));
        });

        return str_replace(self::pixelTag($id), '', $html);
    }

    public static function clickUrl(string $id, string $url): string
    {
        return route('dashed.frontend.mail.click', [
            'id' => $id,
            'u' => rtrim(strtr(base64_encode($url), '+/', '-_'), '='),
            's' => self::signature($id, $url),
        ]);
    }

    /** De bestemming van een kliklink, of null als de handtekening niet klopt. */
    public static function destination(string $id, string $encoded, string $signature): ?string
    {
        $url = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($url === false || ! hash_equals(self::signature($id, $url), $signature)) {
            return null;
        }

        return $url;
    }

    protected static function shouldRewrite(string $url): bool
    {
        return preg_match('#^https?://#i', $url) === 1
            && preg_match(self::SKIP_PATTERN, $url) !== 1;
    }

    protected static function signature(string $id, string $url): string
    {
        return substr(hash_hmac('sha256', $id . '|' . $url, (string) config('app.key')), 0, 32);
    }

    protected static function pixelTag(string $id): string
    {
        return '<img src="' . e(route('dashed.frontend.mail.open', ['id' => $id])) . '" width="1" height="1" alt="" style="border:0;height:1px;width:1px" />';
    }

    /** @param  callable(string): ?string  $replace  null laat de link staan */
    protected static function replaceAnchors(string $html, callable $replace): string
    {
        return preg_replace_callback(self::ANCHOR_PATTERN, function (array $match) use ($replace): string {
            $url = $replace(html_entity_decode(trim($match[3]), ENT_QUOTES | ENT_HTML5));

            return $url === null
                ? $match[0]
                : $match[1] . $match[2] . htmlspecialchars($url, ENT_QUOTES) . $match[2];
        }, $html) ?? $html;
    }
}

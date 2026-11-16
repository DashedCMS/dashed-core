<?php

namespace Dashed\DashedCore\Classes\Redirects;

/**
 * Volgt een redirect tot het eindadres, zodat een bezoeker in één stap aankomt en een lus nooit de deur uit gaat.
 *
 * Lovora (16-11-2026): een NL-redirect /producten/x -> /de/producten/x matchte via het pad zonder taalprefix
 * ook op /de/producten/x en stuurde die naar zichzelf. Google zag een eindeloze lus. Wat hier terugkomt wijst
 * nooit naar het startadres, en een keten is al ingekort.
 */
final class RedirectChain
{
    public const MAX_HOPS = 10;

    public const GONE = 410;

    /**
     * @param  callable(string): (array{to: string|null, status: int}|null)  $lookup  de redirect vanaf een pad, of null
     * @param  string|null  $host  de eigen host: een doel op die host is intern en wordt verder gevolgd
     * @return array{to: string|null, status: int}|null  null bij geen redirect of een lus; to = null bij 410
     */
    public static function resolve(string $path, callable $lookup, ?string $host = null): ?array
    {
        $current = self::normalize($path);
        $seen = [self::key($current) => true];
        $first = null;
        $to = null;

        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            $hit = $lookup($current);

            if ($hit === null) {
                break;
            }

            $first ??= $hit;

            if ((int) $hit['status'] === self::GONE) {
                return ['to' => null, 'status' => self::GONE];
            }

            $to = (string) $hit['to'];

            if (! self::isInternal($to, $host)) {
                return ['to' => $to, 'status' => (int) $first['status']];
            }

            $next = self::normalize($to);

            // Terug bij een adres dat al langskwam: er is geen eindadres, dus geen redirect.
            if (isset($seen[self::key($next)])) {
                return null;
            }

            $seen[self::key($next)] = true;
            $current = $next;
        }

        if ($first === null || $to === null) {
            return null;
        }

        return ['to' => $to, 'status' => (int) $first['status']];
    }

    /**
     * Pad zonder host, query, fragment en slashes aan de randen, met één slash ervoor. Hoofdletters blijven:
     * /en/V en /en/v zijn verschillende paden.
     */
    public static function normalize(?string $url): string
    {
        $url = trim((string) $url);
        $path = preg_match('#^https?://#i', $url) === 1 ? (string) parse_url($url, PHP_URL_PATH) : (string) strtok($url, '?#');

        return '/' . trim(rawurldecode($path), '/');
    }

    /**
     * Twee adressen zijn hetzelfde als de database ze hetzelfde vindt: MySQL vergelijkt `from` zonder op
     * hoofdletters te letten, dus /x -> /X is ook een lus.
     */
    public static function same(?string $a, ?string $b): bool
    {
        return self::key(self::normalize($a)) === self::key(self::normalize($b));
    }

    private static function key(string $path): string
    {
        return mb_strtolower($path);
    }

    public static function isInternal(?string $to, ?string $host = null): bool
    {
        $to = trim((string) $to);

        if ($to === '') {
            return false;
        }

        if (preg_match('#^https?://#i', $to) !== 1) {
            return ! str_starts_with($to, '//');
        }

        return $host !== null && strcasecmp((string) parse_url($to, PHP_URL_HOST), $host) === 0;
    }

    /**
     * De taalprefix van een pad ('de' voor /de/produkte/x), of null zonder prefix.
     *
     * @param  array<int, string>  $locales
     */
    public static function localePrefix(?string $path, array $locales): ?string
    {
        $first = explode('/', trim(self::normalize($path), '/'))[0] ?? '';

        return in_array($first, $locales, true) ? $first : null;
    }

    /**
     * Het pad zonder taalprefix, of null als het pad er geen heeft.
     *
     * @param  array<int, string>  $locales
     */
    public static function withoutLocalePrefix(?string $path, array $locales): ?string
    {
        $prefix = self::localePrefix($path, $locales);

        if ($prefix === null) {
            return null;
        }

        return '/' . ltrim(substr(self::normalize($path), strlen($prefix) + 1), '/');
    }
}

<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Database\Eloquent\Model;

/**
 * Links in blokinhoud aanpassen zonder iets op te slaan. Elke methode geeft de
 * oude en de nieuwe waarde per veld terug; de aanroeper bepaalt wat daarmee
 * gebeurt.
 *
 * Bewust deze vormen, en niet meer:
 *
 *  1. href in HTML uit de rich editor (oudere velden, en pakketten die zelf
 *     nog HTML opslaan)
 *  2. de Tiptap link-mark op een tekstknoop, de vorm waarin de huidige rich
 *     editor een link daadwerkelijk opslaat:
 *     {"type":"text","text":"...","marks":[{"type":"link","attrs":{"href":"...","rel":null}}]}
 *  3. linkHelper-velden met {prefix}_type = 'normal' en een letterlijke
 *     {prefix}_url
 *  4. de ankertekst en het rel-attribuut van zo'n link, in zowel de HTML- als
 *     de mark-vorm
 *
 * Een modelverwijzing ({prefix}_type = 'page' met een {prefix}_page_id) blijft
 * met rust: die lost zijn eigen adres al op en herschrijven zou hem juist
 * kapotmaken. Een adres dat als platte tekst in een alinea staat is geen link
 * en blijft ook staan. Interne links staan in de praktijk als volledig
 * absoluut adres op de eigen host opgeslagen (bijvoorbeeld
 * https://lovora.nl/...), dus de hostvergelijking in matchesInternal() is
 * geen theoretisch geval. Een adres met een ander schema dan http/https
 * (bijvoorbeeld mailto:) wordt nooit als intern adres gezien: zonder die
 * check zou parse_url() zo'n adres een lege host en een pad-achtige waarde
 * geven, en zou het per ongeluk als intern kunnen matchen.
 */
class ContentLinkRewriter
{
    public function __construct(
        protected Model $model,
        protected ?string $locale = null,
    ) {
    }

    /** @return array{changed: bool, before: array<string, mixed>, after: array<string, mixed>} */
    public function rewriteHref(string $fromPath, string $toPath): array
    {
        $from = $this->normalisePath($fromPath);
        $vervanger = fn (string $href): ?string => $this->matchesInternal($href, $from) ? $toPath : null;

        return $this->transform(
            fn (string $html): string => $this->mapHrefs($html, $vervanger),
            $vervanger,
            fn (array $node): array => $this->mapLinkMarks($node, $vervanger),
        );
    }

    /** @return array{changed: bool, before: array<string, mixed>, after: array<string, mixed>} */
    public function setAnchor(string $forPath, string $anchor): array
    {
        $doel = $this->normalisePath($forPath);

        return $this->transform(
            fn (string $html): string => preg_replace_callback(
                '/(<a\b[^>]*href=([\'"])([^\'"]*)\2[^>]*>)(.*?)(<\/a>)/is',
                function (array $m) use ($doel, $anchor): string {
                    // Alleen een werkelijk lege <a></a> invullen. Staat er al
                    // iets tussen, dan is dat een redactionele keuze en blijft
                    // die staan.
                    //
                    // Bewust trim() en niet trim(strip_tags()): een link om een
                    // afbeelding heeft <img ...> als inhoud, en strip_tags()
                    // maakt daar een lege string van. Die guard liet zo'n link
                    // door en de vervanging hieronder gooit de inhoud weg, dus
                    // dan verdwijnt het logo, de banner of de producttegel uit
                    // een gepubliceerde pagina. Zo'n link heeft trouwens geen
                    // ankertekst nodig maar een alt-tekst op de afbeelding.
                    if (! $this->matchesInternal($m[3], $doel) || trim($m[4]) !== '') {
                        return $m[0];
                    }

                    return $m[1] . e($anchor) . $m[5];
                },
                $html,
            ) ?? $html,
            fn (string $url): ?string => null,
            fn (array $node): array => $this->setAnchorOnMarkNode($node, $doel, $anchor),
        );
    }

    /** @return array{changed: bool, before: array<string, mixed>, after: array<string, mixed>} */
    public function removeRel(string $forPath, string $rel): array
    {
        $doel = $this->normalisePath($forPath);

        return $this->transform(
            fn (string $html): string => preg_replace_callback(
                '/<a\b([^>]*)>/i',
                function (array $m) use ($doel, $rel): string {
                    if (! preg_match('/href=([\'"])([^\'"]*)\1/i', $m[1], $href)) {
                        return $m[0];
                    }

                    if (! $this->matchesInternal($href[2], $doel)) {
                        return $m[0];
                    }

                    $attributen = preg_replace_callback(
                        '/rel=([\'"])([^\'"]*)\1/i',
                        function (array $r) use ($rel): string {
                            $over = array_values(array_filter(
                                preg_split('/\s+/', trim($r[2])) ?: [],
                                fn (string $token): bool => $token !== '' && strcasecmp($token, $rel) !== 0,
                            ));

                            // Een leeg rel-attribuut heeft geen betekenis, dus
                            // die valt dan helemaal weg.
                            return $over === [] ? '' : 'rel=' . $r[1] . implode(' ', $over) . $r[1];
                        },
                        $m[1],
                    ) ?? $m[1];

                    return '<a' . rtrim($attributen) . '>';
                },
                $html,
            ) ?? $html,
            fn (string $url): ?string => null,
            fn (array $node): array => $this->removeRelFromMarks($node, $doel, $rel),
        );
    }

    /** @return array{changed: bool, before: array<string, mixed>, after: array<string, mixed>} */
    public function unlink(string $forUrl): array
    {
        $doel = $this->normaliseUrl($forUrl);

        return $this->transform(
            fn (string $html): string => preg_replace_callback(
                '/<a\b[^>]*href=([\'"])([^\'"]*)\1[^>]*>(.*?)<\/a>/is',
                fn (array $m): string => $this->normaliseUrl($m[2]) === $doel ? $m[3] : $m[0],
                $html,
            ) ?? $html,
            fn (string $url): ?string => null,
            fn (array $node): array => $this->removeLinkMarks($node, $doel),
        );
    }

    /**
     * De velden die deze herschrijver langsloopt. Standaard content plus elk
     * vertaalbaar tekstveld; een model mag dat overschrijven.
     *
     * @return array<int, string>
     */
    protected function fields(): array
    {
        if (method_exists($this->model, 'seoRewritableFields')) {
            return $this->model->seoRewritableFields();
        }

        $velden = array_unique(array_merge(['content'], $this->model->translatable ?? []));

        return array_values(array_filter(
            $velden,
            fn (string $veld): bool => ! in_array($veld, ['slug', 'name'], true),
        ));
    }

    /**
     * @param  callable(string): string  $opHtml       past een HTML-string aan
     * @param  callable(string): ?string $opLinkVeld   geeft de nieuwe url voor een linkHelper-veld, of null
     * @param  callable(array): array    $opMarkNode   past een Tiptap-tekstknoop met een link-mark aan
     * @return array{changed: bool, before: array<string, mixed>, after: array<string, mixed>}
     */
    protected function transform(callable $opHtml, callable $opLinkVeld, callable $opMarkNode): array
    {
        $before = [];
        $after = [];
        $changed = false;

        foreach ($this->fields() as $veld) {
            $waarde = $this->read($veld);

            if ($waarde === null) {
                continue;
            }

            $nieuw = $this->walk($waarde, $opHtml, $opLinkVeld, $opMarkNode);

            if ($nieuw === $waarde) {
                continue;
            }

            $before[$veld] = $waarde;
            $after[$veld] = $nieuw;
            $changed = true;
        }

        return ['changed' => $changed, 'before' => $before, 'after' => $after];
    }

    protected function read(string $veld): mixed
    {
        if ($this->locale && method_exists($this->model, 'getTranslation') && in_array($veld, $this->model->translatable ?? [], true)) {
            return $this->model->getTranslation($veld, $this->locale);
        }

        return $this->model->{$veld} ?? null;
    }

    protected function walk(mixed $waarde, callable $opHtml, callable $opLinkVeld, callable $opMarkNode): mixed
    {
        if (is_string($waarde)) {
            return $opHtml($waarde);
        }

        if (! is_array($waarde)) {
            return $waarde;
        }

        // Tiptap-tekstknoop met een link-mark:
        // {"type":"text","text":"...","marks":[{"type":"link","attrs":{...}}]}.
        // Dit is een blad; de aanroeper bepaalt wat ermee gebeurt en we
        // recursen er expliciet niet verder in, want het 'text'-veld is
        // zichtbare tekst, geen HTML, en mag niet door opHtml.
        if (is_array($waarde['marks'] ?? null) && $this->hasLinkMark($waarde['marks'])) {
            return $opMarkNode($waarde);
        }

        // linkHelper: {prefix}_type = 'normal' naast een {prefix}_url. Alleen
        // dan is de url letterlijk opgeslagen en dus van ons.
        foreach ($waarde as $sleutel => $inhoud) {
            if (! is_string($sleutel) || ! str_ends_with($sleutel, '_type') || $inhoud !== 'normal') {
                continue;
            }

            $prefix = substr($sleutel, 0, -strlen('_type'));
            $urlSleutel = $prefix . '_url';

            // Een lege url normaliseert naar '/', dus zonder deze check zou
            // een herschrijving vanaf '/' elk leeg url-veld in het document
            // vullen met het nieuwe adres.
            if (! isset($waarde[$urlSleutel]) || ! is_string($waarde[$urlSleutel]) || $waarde[$urlSleutel] === '') {
                continue;
            }

            $nieuw = $opLinkVeld($waarde[$urlSleutel]);

            if ($nieuw !== null) {
                $waarde[$urlSleutel] = $nieuw;
            }
        }

        foreach ($waarde as $sleutel => $inhoud) {
            $waarde[$sleutel] = $this->walk($inhoud, $opHtml, $opLinkVeld, $opMarkNode);
        }

        return $waarde;
    }

    /** @param array<int, mixed> $marks */
    protected function hasLinkMark(array $marks): bool
    {
        foreach ($marks as $mark) {
            if (is_array($mark) && ($mark['type'] ?? null) === 'link') {
                return true;
            }
        }

        return false;
    }

    /** @param callable(string): ?string $vervanger */
    protected function mapHrefs(string $html, callable $vervanger): string
    {
        // Bewust alleen binnen een <a>-tag. Een href die als zichtbare tekst
        // in een codevoorbeeld staat is geen link, en die mag deze klasse
        // niet aanraken. In opgeslagen HTML is zulke tekst geescaped, dus
        // die begint met &lt;a en matcht hier niet.
        return preg_replace_callback(
            '/<a\b[^>]*>/i',
            function (array $tag) use ($vervanger): string {
                return preg_replace_callback(
                    '/href=([\'"])([^\'"]*)\1/i',
                    function (array $m) use ($vervanger): string {
                        $nieuw = $vervanger($m[2]);

                        return $nieuw === null ? $m[0] : 'href=' . $m[1] . $nieuw . $m[1];
                    },
                    $tag[0],
                ) ?? $tag[0];
            },
            $html,
        ) ?? $html;
    }

    /**
     * Vervangt de href van elke link-mark in een tekstknoop waarvoor
     * $vervanger een nieuwe waarde teruggeeft.
     *
     * @param  callable(string): ?string $vervanger
     */
    protected function mapLinkMarks(array $node, callable $vervanger): array
    {
        foreach ($node['marks'] as $i => $mark) {
            if (! is_array($mark) || ($mark['type'] ?? null) !== 'link') {
                continue;
            }

            $href = $mark['attrs']['href'] ?? null;

            if (! is_string($href)) {
                continue;
            }

            $nieuw = $vervanger($href);

            if ($nieuw !== null) {
                $node['marks'][$i]['attrs']['href'] = $nieuw;
            }
        }

        return $node;
    }

    /**
     * Vult de tekst van een tekstknoop met een matchende link-mark, maar
     * alleen als die tekst nog leeg is. Precies dezelfde terughoudendheid
     * als de HTML-vorm van setAnchor().
     */
    protected function setAnchorOnMarkNode(array $node, string $doel, string $anchor): array
    {
        $heeftMatch = false;

        foreach ($node['marks'] as $mark) {
            if (! is_array($mark) || ($mark['type'] ?? null) !== 'link') {
                continue;
            }

            $href = $mark['attrs']['href'] ?? null;

            if (is_string($href) && $this->matchesInternal($href, $doel)) {
                $heeftMatch = true;

                break;
            }
        }

        if (! $heeftMatch || trim((string) ($node['text'] ?? '')) !== '') {
            return $node;
        }

        $node['text'] = $anchor;

        return $node;
    }

    /**
     * Haalt $rel uit het rel-attribuut van elke matchende link-mark en laat
     * de rest staan. rel staat vaak op null; dan is er niets te verwijderen.
     */
    protected function removeRelFromMarks(array $node, string $doel, string $rel): array
    {
        foreach ($node['marks'] as $i => $mark) {
            if (! is_array($mark) || ($mark['type'] ?? null) !== 'link') {
                continue;
            }

            $href = $mark['attrs']['href'] ?? null;

            if (! is_string($href) || ! $this->matchesInternal($href, $doel)) {
                continue;
            }

            $relWaarde = $mark['attrs']['rel'] ?? null;

            if (! is_string($relWaarde) || trim($relWaarde) === '') {
                continue;
            }

            $over = array_values(array_filter(
                preg_split('/\s+/', trim($relWaarde)) ?: [],
                fn (string $token): bool => $token !== '' && strcasecmp($token, $rel) !== 0,
            ));

            // Een leeg rel-attribuut heeft geen betekenis, dus die valt dan
            // terug naar null, zoals rel ook staat wanneer er nooit een
            // waarde is gezet.
            $node['marks'][$i]['attrs']['rel'] = $over === [] ? null : implode(' ', $over);
        }

        return $node;
    }

    /**
     * Verwijdert elke link-mark die naar $doel wijst uit de marks-array,
     * maar laat de tekstknoop zelf staan. Een lege marks-array blijft een
     * lege array, de sleutel verdwijnt niet.
     */
    protected function removeLinkMarks(array $node, string $doel): array
    {
        $node['marks'] = array_values(array_filter($node['marks'], function ($mark) use ($doel): bool {
            if (! is_array($mark) || ($mark['type'] ?? null) !== 'link') {
                return true;
            }

            $href = $mark['attrs']['href'] ?? null;

            if (! is_string($href)) {
                return true;
            }

            return $this->normaliseUrl($href) !== $doel;
        }));

        return $node;
    }

    /**
     * Wijst dit adres naar hetzelfde interne pad? Een adres op een andere host
     * telt niet mee, ook niet als het pad toevallig gelijk is. Een adres met
     * een ander schema dan http/https (bijvoorbeeld mailto:) telt nooit mee.
     */
    protected function matchesInternal(string $url, string $pad): bool
    {
        $schema = parse_url($url, PHP_URL_SCHEME);

        if ($schema !== null && ! in_array(strtolower($schema), ['http', 'https'], true)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host !== parse_url((string) config('app.url'), PHP_URL_HOST)) {
            return false;
        }

        return $this->normalisePath($url) === $pad;
    }

    protected function normalisePath(string $url): string
    {
        $pad = parse_url($url, PHP_URL_PATH) ?: '/';

        return $pad === '/' ? '/' : '/' . trim($pad, '/');
    }

    protected function normaliseUrl(string $url): string
    {
        $delen = parse_url($url);

        if (! is_array($delen)) {
            return $url;
        }

        $host = $delen['host'] ?? '';
        $pad = $this->normalisePath($url);

        return strtolower($host) . $pad;
    }
}

<?php

namespace Dashed\DashedCore\Classes\Editor;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\SchemaColumns;

/**
 * Interne-linksuggesties voor de tekst in een bewerkscherm, zonder AI.
 *
 * Werkwijze: de lopende tekst uit de blokken (Tiptap-tekst en HTML, zonder
 * wat al een link is), daaruit de vaakst voorkomende woorden van minstens
 * vier letters, en per woord een zoekopdracht via de eigen `search`-scope van
 * elk aangemeld routemodel (TokenizedSearch, dus hetzelfde als elk zoekveld
 * in het CMS). Een kandidaat telt alleen als zijn naam, of een woord ervan
 * van minstens zes tekens, letterlijk los in de tekst staat; dat stuk tekst
 * is de ankertekst. Een volledige naam weegt zwaarder dan een los woord. Het record zelf, adressen
 * waar de tekst al naar linkt, en wat niet publiek is (site, publiek,
 * start- en einddatum) vallen af.
 */
class LinkSuggestionFinder
{
    public const MAX_WORDS = 8;

    public const PER_WORD = 5;

    public const MAX_RESULTS = 15;

    public const MIN_SINGLE_WORD = 6;

    /** Veelvoorkomende woorden die nooit een goed anker zijn. */
    private const STOPWORDS = [
        'deze', 'onze', 'jouw', 'voor', 'naar', 'over', 'maar', 'waar', 'want', 'meer', 'zijn', 'wordt', 'worden',
        'kunt', 'kunnen', 'heeft', 'hebben', 'zoals', 'ook', 'niet', 'alle', 'veel', 'door', 'tussen', 'onder',
        'this', 'that', 'with', 'from', 'your', 'have', 'they', 'what', 'will', 'more', 'about', 'their', 'there',
        'dieser', 'diese', 'eine', 'einer', 'nicht', 'auch', 'oder', 'sind', 'wird', 'werden', 'mehr',
    ];

    /**
     * @return array<int, array{anchor: string, url: string, title: string, type: string, score: int}>
     */
    public static function find(?Model $record, array $content, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        [$text, $linked] = static::textAndLinks($content);

        if (trim($text) === '') {
            return [];
        }

        $words = static::significantWords($text);
        $suggestions = [];

        foreach (cms()->builder('routeModels') as $routeModel) {
            $class = $routeModel['class'] ?? null;
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $candidates = collect();
            foreach ($words as $word) {
                $candidates = $candidates->merge(rescue(fn () => static::search($class, $word), [], report: false));
            }

            foreach ($candidates->unique(fn (Model $m) => $m->getKey()) as $candidate) {
                if ($record && $candidate::class === $record::class && $candidate->getKey() === $record->getKey()) {
                    continue;
                }

                $title = static::name($candidate, $locale, $routeModel['nameField'] ?? 'name');
                $url = rescue(fn () => (string) $candidate->getUrl($locale), '', report: false);
                if ($title === '' || $url === '' || in_array(static::normalise($url), $linked, true)) {
                    continue;
                }

                $match = static::anchorFor($title, $text);
                if (! $match) {
                    continue;
                }

                $suggestions[] = [
                    'anchor' => $match['anchor'],
                    'url' => $url,
                    'title' => $title,
                    'type' => (string) ($routeModel['name'] ?? class_basename($class)),
                    'score' => $match['score'],
                ];
            }
        }

        return collect($suggestions)
            ->unique(fn ($s) => static::normalise($s['url']))
            ->sortByDesc('score')
            ->take(self::MAX_RESULTS)
            ->values()
            ->all();
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    public static function textAndLinks(array $content): array
    {
        $texts = [];
        $links = [];

        $walk = function (mixed $value, ?string $key = null) use (&$walk, &$texts, &$links): void {
            if (is_array($value)) {
                if (($value['type'] ?? null) === 'text' && is_string($value['text'] ?? null)) {
                    $href = collect($value['marks'] ?? [])->first(fn ($m) => is_array($m) && ($m['type'] ?? null) === 'link')['attrs']['href'] ?? null;
                    if ($href) {
                        $links[] = (string) $href;
                    } else {
                        $texts[] = $value['text'];
                    }

                    return;
                }

                foreach ($value as $k => $child) {
                    if (in_array($k, ['type', 'url', 'href', 'id', 'slug', 'image', 'images', 'icon'], true)) {
                        continue;
                    }
                    $walk($child, is_string($k) ? $k : $key);
                }

                return;
            }

            if (is_string($value) && str_contains($value, '<')) {
                preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $value, $m);
                array_push($links, ...$m[1]);
                $withoutLinks = preg_replace('/<a\s[^>]*>.*?<\/a>/is', ' ', $value) ?? $value;
                $texts[] = html_entity_decode(strip_tags(str_replace('>', '> ', $withoutLinks)), ENT_QUOTES | ENT_HTML5);
            } elseif (is_string($value) && in_array($key, ['title', 'heading', 'subtitle', 'intro', 'content', 'text', 'description'], true)) {
                $texts[] = $value;
            }
        };

        $walk($content);

        return [
            trim(preg_replace('/\s+/u', ' ', implode(' ', $texts)) ?? ''),
            array_values(array_unique(array_map([static::class, 'normalise'], $links))),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function significantWords(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\-]{3,}/u', Str::lower($text), $m);

        return collect($m[0])
            ->reject(fn ($w) => in_array($w, self::STOPWORDS, true) || is_numeric($w))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(self::MAX_WORDS)
            ->values()
            ->all();
    }

    /**
     * @return array{anchor: string, score: int}|null
     */
    public static function anchorFor(string $title, string $text): ?array
    {
        $pattern = fn (string $needle) => '/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/iu';

        if (preg_match($pattern($title), $text, $m)) {
            return ['anchor' => $m[0], 'score' => 100 + mb_strlen($title)];
        }

        $words = collect(preg_split('/[^\p{L}\p{N}\-]+/u', $title) ?: [])
            // Een los woord uit de naam alleen als het lang genoeg is om iets
            // te zeggen: "thee" past op elke theepagina, "rooibos" niet.
            ->filter(fn ($w) => mb_strlen($w) >= self::MIN_SINGLE_WORD && ! in_array(Str::lower($w), self::STOPWORDS, true))
            ->sortByDesc(fn ($w) => mb_strlen($w));

        foreach ($words as $word) {
            if (preg_match($pattern($word), $text, $m)) {
                return ['anchor' => $m[0], 'score' => 10 + mb_strlen($word)];
            }
        }

        return null;
    }

    /**
     * @return iterable<Model>
     */
    protected static function search(string $class, string $word): iterable
    {
        /** @var Model $instance */
        $instance = new $class();
        $table = $instance->getTable();
        $query = $class::query();

        if (! $query->hasNamedScope('search')) {
            return [];
        }

        $query->search($word);

        if ($query->hasNamedScope('thisSite')) {
            $query->thisSite();
        }
        if ($query->hasNamedScope('isPublic')) {
            $query->isPublic();
        }
        if (SchemaColumns::has($table, 'start_date')) {
            $query->where(fn ($q) => $q->whereNull("{$table}.start_date")->orWhere("{$table}.start_date", '<=', now()));
        }
        if (SchemaColumns::has($table, 'end_date')) {
            $query->where(fn ($q) => $q->whereNull("{$table}.end_date")->orWhere("{$table}.end_date", '>=', now()));
        }

        return $query->limit(self::PER_WORD)->get();
    }

    /**
     * `nameField` uit registerRouteModel() is niet betrouwbaar: een deel van
     * de pakketten geeft daar het meervoud mee ("Pagina's"). Daarom eerst
     * name en title, en het opgegeven veld alleen als het een kolomnaam is.
     */
    protected static function name(Model $model, string $locale, string $field): string
    {
        $fields = array_unique(array_filter(['name', 'title', preg_match('/^[a-z_]+$/', $field) ? $field : null]));

        foreach ($fields as $candidate) {
            $value = method_exists($model, 'getTranslation') && in_array($candidate, $model->translatable ?? [], true)
                ? $model->getTranslation($candidate, $locale, true)
                : $model->getAttribute($candidate);

            if (is_string($value) && trim(strip_tags($value)) !== '') {
                return trim(strip_tags($value));
            }
        }

        return '';
    }

    public static function normalise(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        return '/'.trim(Str::lower((string) $path), '/');
    }
}

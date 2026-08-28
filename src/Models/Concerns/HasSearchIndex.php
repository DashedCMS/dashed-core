<?php

namespace Dashed\DashedCore\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Search\SearchIndexer;

trait HasSearchIndex
{
    protected static bool $searchIndexingDisabled = false;

    public static function bootHasSearchIndex(): void
    {
        static::saved(function ($model): void {
            if (static::$searchIndexingDisabled) {
                return;
            }
            app(SearchIndexer::class)->index($model);
        });

        static::deleted(function ($model): void {
            if (static::$searchIndexingDisabled) {
                return;
            }
            app(SearchIndexer::class)->remove($model);
        });
    }

    public static function withoutSearchIndexing(callable $callback)
    {
        $previous = static::$searchIndexingDisabled;
        static::$searchIndexingDisabled = true;

        try {
            return $callback();
        } finally {
            static::$searchIndexingDisabled = $previous;
        }
    }

    /**
     * Locales om te indexeren: alle locales waarvoor vertalingen bestaan,
     * plus de huidige app-locale als vangnet.
     */
    public function searchIndexLocales(): array
    {
        $locales = [];

        foreach ($this->getSearchIndexAttributes() as $attribute) {
            if (method_exists($this, 'getTranslatedLocales')) {
                $locales = array_merge($locales, $this->getTranslatedLocales($attribute));
            }
        }

        $locales[] = app()->getLocale();

        return array_values(array_unique(array_filter($locales)));
    }

    /**
     * Vertaalbare tekstvelden die de standaard-index vullen (relaties overslaan).
     */
    protected function getSearchIndexAttributes(): array
    {
        if (! method_exists($this, 'getTranslatableAttributes')) {
            return [];
        }

        return collect($this->getTranslatableAttributes())
            ->reject(fn ($attr) => method_exists($this, $attr))
            ->values()
            ->all();
    }

    /**
     * Bouwt de gedenormaliseerde tekst voor één locale. Override voor extra
     * velden (bijv. relatie-tekst) of keywords (bijv. sku/ean).
     */
    public function toSearchIndexArray(string $locale): array
    {
        $parts = [];

        foreach ($this->getSearchIndexAttributes() as $attribute) {
            $value = method_exists($this, 'getTranslation')
                ? $this->getTranslation($attribute, $locale, false)
                : ($this->{$attribute} ?? null);

            $parts[] = $this->normalizeSearchText($value);
        }

        return [
            'text' => trim(implode(' ', array_filter($parts))),
            'keywords' => '',
        ];
    }

    protected function normalizeSearchText($value): string
    {
        if (is_array($value)) {
            $value = implode(' ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $value));
        }

        $value = (string) $value;
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim(mb_strtolower($value));
    }

    public function scopeSearchIndexed($query, ?string $term, ?string $locale = null)
    {
        $term = trim((string) $term);

        if ($term === '') {
            // Lege zoekterm levert geen resultaten (i.t.t. scopeSearch die alles teruggeeft).
            return $query->whereRaw('1 = 0');
        }

        $locale = $locale ?: app()->getLocale();
        $needle = mb_strtolower($term);
        $morph = $this->getMorphClass();
        $table = $this->getTable();
        $keyName = $this->getKeyName();
        $driver = $query->getConnection()->getDriverName();

        $index = DB::table(SearchIndexer::TABLE)
            ->where('searchable_type', $morph)
            ->where('locale', $locale);

        if ($driver === 'mysql' && mb_strlen($needle) >= 3) {
            $boolean = $this->buildFulltextBoolean($needle);

            $index->select('searchable_id')
                ->selectRaw(
                    'MATCH(search_text) AGAINST (? IN BOOLEAN MODE) + (CASE WHEN keywords LIKE ? THEN 1000 ELSE 0 END) as search_relevance',
                    [$boolean, '%'.$needle.'%']
                )
                ->where(function ($q) use ($boolean, $needle) {
                    $q->whereRaw('MATCH(search_text) AGAINST (? IN BOOLEAN MODE)', [$boolean])
                        ->orWhere('keywords', 'LIKE', '%'.$needle.'%');
                });
        } else {
            $index->select('searchable_id')
                ->selectRaw(
                    '(CASE WHEN keywords LIKE ? THEN 1000 WHEN search_text LIKE ? THEN 1 ELSE 0 END) as search_relevance',
                    ['%'.$needle.'%', '%'.$needle.'%']
                )
                ->where(function ($q) use ($needle) {
                    $q->where('search_text', 'LIKE', '%'.$needle.'%')
                        ->orWhere('keywords', 'LIKE', '%'.$needle.'%');
                });
        }

        return $query
            ->joinSub($index, 'search_idx', function ($join) use ($table, $keyName) {
                $join->on($table.'.'.$keyName, '=', 'search_idx.searchable_id');
            })
            ->addSelect($table.'.*')
            ->addSelect('search_idx.search_relevance')
            ->orderByDesc('search_idx.search_relevance');
    }

    /**
     * Zet de zoekterm om naar een BOOLEAN MODE expressie met prefix-matching,
     * zodat "fie" ook "fiets" vindt. Niet-alfanumerieke tekens worden scheiding.
     */
    protected function buildFulltextBoolean(string $needle): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = array_map(fn ($word) => '+'.$word.'*', $words);

        return implode(' ', $words);
    }
}

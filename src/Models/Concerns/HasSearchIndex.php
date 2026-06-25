<?php

namespace Dashed\DashedCore\Models\Concerns;

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
}

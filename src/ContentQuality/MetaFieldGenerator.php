<?php

namespace Dashed\DashedCore\ContentQuality;

use Dashed\DashedAi\Facades\Ai;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\ContentQuality\Checks\MetaLengthCheck;

class MetaFieldGenerator
{
    /**
     * @param  array<int, string>  $missingLocales
     * @return array<string, string>  locale => generated value
     */
    public function generate(Model $model, string $field, array $missingLocales): array
    {
        if ($missingLocales === []) {
            return [];
        }

        $name = method_exists($model, 'getTranslation')
            ? ($model->getTranslation('name', $missingLocales[0], false) ?: ($model->name ?? ''))
            : ($model->name ?? '');

        $limit = MetaLengthCheck::LIMITS[$field] ?? 170;
        $what = $field === 'title' ? 'SEO meta-titel' : 'SEO meta-omschrijving';
        $locales = implode(', ', $missingLocales);

        $prompt = "Genereer een pakkende {$what} (max {$limit} tekens) voor de content getiteld \"{$name}\". "
            . "Geef voor elke taal in deze lijst een waarde: {$locales}. "
            . 'Antwoord als JSON-object met de taalcode als sleutel en de tekst als waarde. Geen extra uitleg.';

        $result = Ai::json($prompt);

        $out = [];
        foreach ($missingLocales as $locale) {
            if (is_array($result) && filled($result[$locale] ?? null)) {
                $out[$locale] = (string) $result[$locale];
            }
        }

        return $out;
    }

    /**
     * Rewrites existing meta texts that are too long or were cut off
     * mid-sentence into a complete text within the limit, per locale and
     * in that locale's language.
     *
     * @param  array<int, string>  $locales
     * @return array<string, string>  locale => rewritten value
     */
    public function rewrite(Model $model, string $field, array $locales): array
    {
        $metadata = $model->metadata;
        if (! $metadata || $locales === []) {
            return [];
        }

        $current = [];
        foreach ($locales as $locale) {
            $value = (string) $metadata->getTranslation($field, $locale, false);
            if ($value !== '') {
                $current[$locale] = $value;
            }
        }
        if ($current === []) {
            return [];
        }

        // A little under the hard limit: models tend to overshoot a bit.
        $limit = (MetaLengthCheck::LIMITS[$field] ?? 170) - 10;
        $what = $field === 'title' ? 'SEO meta-titel' : 'SEO meta-omschrijving';

        $prompt = "Herschrijf elke {$what} hieronder tot een complete, natuurlijke tekst van maximaal {$limit} tekens, "
            . 'in dezelfde taal als het origineel. Sommige teksten zijn midden in een woord of zin afgebroken: '
            . 'maak de gedachte af in plaats van het afgebroken stuk over te nemen. Behoud merknamen en de kern van de boodschap. '
            . 'Antwoord als JSON-object met de taalcode als sleutel en de nieuwe tekst als waarde. Geen extra uitleg.'
            . "\n\n" . json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $result = Ai::json($prompt);

        $out = [];
        foreach (array_keys($current) as $locale) {
            $value = is_array($result) ? trim((string) ($result[$locale] ?? '')) : '';
            if ($value !== '' && mb_strlen($value) <= MetaLengthCheck::LIMITS[$field]) {
                $out[$locale] = $value;
            }
        }

        return $out;
    }
}

<?php

namespace Dashed\DashedCore\ContentQuality;

use Dashed\DashedAi\Facades\Ai;
use Illuminate\Database\Eloquent\Model;

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

        $limit = $field === 'title' ? 70 : 170;
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
}

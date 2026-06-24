<?php

// packages/dashed/dashed-core/src/ContentQuality/Checks/MetaTooLongCheck.php

namespace Dashed\DashedCore\ContentQuality\Checks;

use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\ContentQuality\QualityIssue;
use Dashed\DashedCore\ContentQuality\ContentQualityRegistry;
use Dashed\DashedCore\ContentQuality\Contracts\ContentQualityCheck;

class MetaTooLongCheck implements ContentQualityCheck
{
    protected const LIMITS = ['title' => 70, 'description' => 170];

    public function key(): string
    {
        return 'meta_too_long';
    }

    public function label(): string
    {
        return 'Meta-tekst te lang';
    }

    public function count(string $siteId): int
    {
        return $this->items($siteId)->count();
    }

    public function items(string $siteId): Collection
    {
        $locales = Sites::getLocales($siteId)->pluck('id')->all();
        $issues = collect();

        foreach (app(ContentQualityRegistry::class)->models() as $registered) {
            $modelClass = $registered->modelClass;

            $modelClass::query()
                ->with('metadata')
                ->get()
                ->each(function ($model) use ($issues, $locales, $registered, $siteId) {
                    $siteIds = $model->site_ids ?? [];
                    if ($siteIds !== [] && ! in_array($siteId, $siteIds, true)) {
                        return;
                    }
                    if (! $model->metadata) {
                        return;
                    }

                    foreach (self::LIMITS as $field => $limit) {
                        foreach ($locales as $locale) {
                            $value = (string) $model->metadata->getTranslation($field, $locale, false);
                            if (mb_strlen($value) > $limit) {
                                $issues->push(new QualityIssue(
                                    checkKey: 'meta_too_long',
                                    title: $this->displayName($model),
                                    subtitle: ucfirst($field) . ' te lang (' . mb_strlen($value) . '/' . $limit . ') in ' . strtoupper($locale),
                                    editUrl: $registered->resourceClass::getUrl('edit', ['record' => $model]),
                                    modelClass: $registered->modelClass,
                                    modelId: $model->getKey(),
                                    missingLocales: [$locale],
                                ));

                                return; // one issue per model is enough
                            }
                        }
                    }
                });
        }

        return $issues;
    }

    public function resolutions(): array
    {
        return ['link'];
    }

    protected function displayName($model): string
    {
        if (method_exists($model, 'getTranslation')) {
            $name = $model->getTranslation('name', app()->getLocale(), false);
            if (filled($name)) {
                return $name;
            }
        }

        return $model->name ?? ('#' . $model->getKey());
    }
}

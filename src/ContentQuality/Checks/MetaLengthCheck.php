<?php

// packages/dashed/dashed-core/src/ContentQuality/Checks/MetaLengthCheck.php

namespace Dashed\DashedCore\ContentQuality\Checks;

use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\ContentQuality\QualityIssue;
use Dashed\DashedCore\ContentQuality\ContentQualityRegistry;
use Dashed\DashedCore\ContentQuality\Contracts\ContentQualityCheck;

/**
 * Base for checks on the length of an existing meta text. Reports one issue
 * per model and field, listing every locale that fails, so the dashboard can
 * edit or rewrite exactly those translations.
 */
abstract class MetaLengthCheck implements ContentQualityCheck
{
    public const LIMITS = ['title' => 70, 'description' => 170];

    /** @return array<int, string> */
    abstract protected function fields(): array;

    abstract protected function fails(string $value, int $limit): bool;

    abstract protected function describe(string $field, string $locale, string $value, int $limit): string;

    public function count(string $siteId): int
    {
        return $this->items($siteId)->count();
    }

    public function items(string $siteId): Collection
    {
        $locales = Sites::getLocales($siteId)->pluck('id')->all();
        $issues = collect();

        foreach (app(ContentQualityRegistry::class)->modelsFor($this->key()) as $registered) {
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

                    foreach ($this->fields() as $field) {
                        $limit = self::LIMITS[$field];
                        $failing = [];
                        $details = [];

                        foreach ($locales as $locale) {
                            $value = (string) $model->metadata->getTranslation($field, $locale, false);
                            if ($value !== '' && $this->fails($value, $limit)) {
                                $failing[] = $locale;
                                $details[] = $this->describe($field, $locale, $value, $limit);
                            }
                        }

                        if ($failing !== []) {
                            $issues->push(new QualityIssue(
                                checkKey: $this->key(),
                                title: $this->displayName($model),
                                subtitle: implode(', ', $details),
                                editUrl: $registered->resourceClass::getUrl('edit', ['record' => $model]),
                                modelClass: $registered->modelClass,
                                modelId: $model->getKey(),
                                missingLocales: $failing,
                                field: $field,
                            ));
                        }
                    }
                });
        }

        return $issues;
    }

    public function resolutions(): array
    {
        return ['inline', 'ai', 'bulk_ai', 'link'];
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

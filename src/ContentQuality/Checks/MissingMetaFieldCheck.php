<?php

// packages/dashed/dashed-core/src/ContentQuality/Checks/MissingMetaFieldCheck.php

namespace Dashed\DashedCore\ContentQuality\Checks;

use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\ContentQuality\QualityIssue;
use Dashed\DashedCore\ContentQuality\ContentQualityRegistry;
use Dashed\DashedCore\ContentQuality\Contracts\ContentQualityCheck;

class MissingMetaFieldCheck implements ContentQualityCheck
{
    public function __construct(
        protected string $field,
        protected string $key,
        protected string $label,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
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
                    if (! $this->belongsToSite($model, $siteId)) {
                        return;
                    }

                    $missing = $this->missingLocales($model, $locales);
                    if ($missing === []) {
                        return;
                    }

                    $issues->push(new QualityIssue(
                        checkKey: $this->key,
                        title: $this->displayName($model, $locales),
                        subtitle: 'mist in ' . strtoupper(implode(', ', $missing)),
                        editUrl: $registered->resourceClass::getUrl('edit', ['record' => $model]),
                        modelClass: $registered->modelClass,
                        modelId: $model->getKey(),
                        missingLocales: $missing,
                    ));
                });
        }

        return $issues;
    }

    public function resolutions(): array
    {
        // Meta image cannot be AI-generated in this scope.
        return $this->field === 'image'
            ? ['link']
            : ['inline', 'ai', 'bulk_ai', 'link'];
    }

    protected function missingLocales($model, array $locales): array
    {
        $metadata = $model->metadata;
        $missing = [];

        foreach ($locales as $locale) {
            $value = $metadata ? $metadata->getTranslation($this->field, $locale, false) : '';
            if (blank($value)) {
                $missing[] = $locale;
            }
        }

        return $missing;
    }

    protected function belongsToSite($model, string $siteId): bool
    {
        $siteIds = $model->site_ids ?? [];

        return $siteIds === [] || in_array($siteId, $siteIds, true);
    }

    protected function displayName($model, array $locales): string
    {
        if (method_exists($model, 'getTranslation')) {
            foreach ($locales as $locale) {
                $name = $model->getTranslation('name', $locale, false);
                if (filled($name)) {
                    return $name;
                }
            }
        }

        return $model->name ?? ('#' . $model->getKey());
    }
}

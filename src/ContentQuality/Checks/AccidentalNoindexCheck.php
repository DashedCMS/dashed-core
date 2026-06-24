<?php

// packages/dashed/dashed-core/src/ContentQuality/Checks/AccidentalNoindexCheck.php

namespace Dashed\DashedCore\ContentQuality\Checks;

use Illuminate\Support\Collection;
use Dashed\DashedCore\ContentQuality\QualityIssue;
use Dashed\DashedCore\ContentQuality\ContentQualityRegistry;
use Dashed\DashedCore\ContentQuality\Contracts\ContentQualityCheck;

class AccidentalNoindexCheck implements ContentQualityCheck
{
    public function key(): string
    {
        return 'accidental_noindex';
    }

    public function label(): string
    {
        return 'Op noindex gezet';
    }

    public function count(string $siteId): int
    {
        return $this->items($siteId)->count();
    }

    public function items(string $siteId): Collection
    {
        $issues = collect();

        foreach (app(ContentQualityRegistry::class)->models() as $registered) {
            $modelClass = $registered->modelClass;

            $modelClass::query()
                ->with('metadata')
                ->get()
                ->each(function ($model) use ($issues, $registered, $siteId) {
                    $siteIds = $model->site_ids ?? [];
                    if ($siteIds !== [] && ! in_array($siteId, $siteIds, true)) {
                        return;
                    }

                    if (! $model->metadata || ! $model->metadata->noindex) {
                        return;
                    }

                    $issues->push(new QualityIssue(
                        checkKey: 'accidental_noindex',
                        title: $this->displayName($model),
                        subtitle: 'staat op noindex',
                        editUrl: $registered->resourceClass::getUrl('edit', ['record' => $model]),
                        modelClass: $registered->modelClass,
                        modelId: $model->getKey(),
                    ));
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

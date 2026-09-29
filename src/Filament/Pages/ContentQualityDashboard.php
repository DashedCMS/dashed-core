<?php

namespace Dashed\DashedCore\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Dashed\DashedAi\Facades\Ai;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedAi\Enums\AiCapability;
use Dashed\DashedAi\Jobs\CreateAltTextForMediaItem;
use Dashed\DashedCore\ContentQuality\MetaFieldGenerator;
use Dashed\DashedCore\ContentQuality\ContentQualityScanner;
use RalphJSmit\Filament\MediaLibrary\Models\MediaLibraryItem;
use Dashed\DashedCore\ContentQuality\Jobs\GenerateMetaFieldForModel;

class ContentQualityDashboard extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'SEO & site';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Content-kwaliteit';

    protected static ?string $title = 'Content-kwaliteit';

    protected static ?string $slug = 'content-quality';

    protected string $view = 'dashed-core::pages.content-quality-dashboard';

    public ?string $selectedCheck = null;

    public array $inlineTarget = [];

    public array $inlineValues = [];

    /**
     * Checks on an existing meta text: inline editing starts from the current
     * text and "Fix met AI" rewrites it instead of generating a new one.
     */
    public const REWRITE_CHECKS = ['meta_too_long', 'meta_truncated'];

    public static function canAccess(): bool
    {
        return (bool) auth()->user();
    }

    public function getCardsProperty(): array
    {
        return app(ContentQualityScanner::class)->counts(Sites::getActive());
    }

    public function getIssuesProperty(): Collection
    {
        if (! $this->selectedCheck) {
            return collect();
        }

        return app(ContentQualityScanner::class)->issues(Sites::getActive(), $this->selectedCheck);
    }

    public function selectCheck(string $key): void
    {
        $this->selectedCheck = $key;
    }

    public function rescan(): void
    {
        app(ContentQualityScanner::class)->rescan(Sites::getActive());
        $this->dispatch('$refresh');
    }

    public function editInline(string $checkKey, ?int $mediaId, ?string $modelClass, int|string|null $modelId, ?string $field = null): void
    {
        $this->inlineTarget = [
            'checkKey' => $checkKey,
            'mediaId' => $mediaId,
            'modelClass' => $modelClass,
            'modelId' => $modelId,
            'field' => $field,
        ];
        $this->inlineValues = [];

        if ($mediaId) {
            $this->inlineValues = ['alt' => ''];

            return;
        }

        // Seed one input per affected locale for the targeted meta field;
        // for length checks start from the current text so it can be trimmed.
        $issue = $this->findIssue($checkKey, $modelClass, $modelId, $field);
        $metadata = $modelClass && in_array($checkKey, self::REWRITE_CHECKS, true)
            ? $modelClass::find($modelId)?->metadata
            : null;
        $metaField = $this->fieldForCheck($checkKey, $field);
        foreach (($issue?->missingLocales ?? []) as $locale) {
            $this->inlineValues[$locale] = $metadata
                ? (string) $metadata->getTranslation($metaField, $locale, false)
                : '';
        }
    }

    public function saveInline(bool $openNext = false): void
    {
        $target = $this->inlineTarget;
        $position = $this->issues->search(fn ($i) => $this->isTarget($i, $target));

        if ($target['mediaId'] ?? null) {
            $item = MediaLibraryItem::withoutGlobalScopes()->find($target['mediaId']);
            if ($item) {
                $item->alt_text = trim((string) ($this->inlineValues['alt'] ?? ''));
                $item->save();
            }
        } else {
            $modelClass = $target['modelClass'];
            $model = $modelClass::find($target['modelId']);
            if ($model) {
                $metadata = $model->metadata ?: $model->metadata()->make();
                $field = $this->fieldForCheck($target['checkKey'], $target['field'] ?? null);
                foreach ($this->inlineValues as $locale => $value) {
                    $metadata->setTranslation($field, $locale, trim((string) $value));
                }
                $model->metadata()->save($metadata);
            }
        }

        $this->rescan();
        $this->inlineTarget = [];
        $this->inlineValues = [];
        unset($this->issues);

        if ($openNext && $position !== false) {
            $this->openNextAfter($position, $target);
        }
    }

    /**
     * Opens the issue that now sits where the saved one was. If the saved
     * item is still listed (e.g. still too long), move one further.
     */
    protected function openNextAfter(int $position, array $saved): void
    {
        $remaining = $this->issues->values();
        $next = $remaining->get($position);
        if ($next && $this->isTarget($next, $saved)) {
            $next = $remaining->get($position + 1);
        }
        if (! $next) {
            return;
        }

        $this->editInline($next->checkKey, $next->mediaId, $next->modelClass, $next->modelId, $next->field);
    }

    protected function isTarget($issue, array $target): bool
    {
        return $issue->checkKey === ($target['checkKey'] ?? null)
            && ($issue->mediaId ?? null) == ($target['mediaId'] ?? null)
            && (string) ($issue->modelId ?? '') === (string) ($target['modelId'] ?? '')
            && ($issue->field ?? null) === ($target['field'] ?? null);
    }

    protected function findIssue(string $checkKey, ?string $modelClass, int|string|null $modelId, ?string $field)
    {
        return $this->issues->first(
            fn ($i) => $i->modelClass === $modelClass
                && (string) $i->modelId === (string) $modelId
                && $i->checkKey === $checkKey
                && ($field === null || $i->field === $field)
        );
    }

    protected function fieldForCheck(string $checkKey, ?string $field = null): string
    {
        if ($field) {
            return $field;
        }

        return match ($checkKey) {
            'missing_meta_title' => 'title',
            'missing_meta_description' => 'description',
            'missing_meta_image' => 'image',
            default => 'title',
        };
    }

    public function aiFix(string $checkKey, ?int $mediaId, ?string $modelClass, int|string|null $modelId, ?string $field = null): void
    {
        if ($mediaId) {
            $item = MediaLibraryItem::withoutGlobalScopes()->find($mediaId);
            if ($item) {
                CreateAltTextForMediaItem::dispatch($item);
            }

            return;
        }

        if (! $modelClass) {
            return;
        }

        $model = $modelClass::find($modelId);
        if (! $model) {
            return;
        }

        $issue = $this->findIssue($checkKey, $modelClass, $modelId, $field);
        $field = $this->fieldForCheck($checkKey, $field);
        $missing = $issue?->missingLocales ?? [];

        $generator = app(MetaFieldGenerator::class);
        $generated = in_array($checkKey, self::REWRITE_CHECKS, true)
            ? $generator->rewrite($model, $field, $missing)
            : $generator->generate($model, $field, $missing);
        if ($generated === []) {
            return;
        }

        $metadata = $model->metadata ?: $model->metadata()->make();
        foreach ($generated as $locale => $value) {
            $metadata->setTranslation($field, $locale, $value);
        }
        $model->metadata()->save($metadata);

        $this->rescan();
    }

    public function bulkAiFix(): void
    {
        if (! $this->selectedCheck) {
            return;
        }

        $rewrite = in_array($this->selectedCheck, self::REWRITE_CHECKS, true);

        foreach ($this->issues as $issue) {
            if ($issue->mediaId) {
                $item = MediaLibraryItem::withoutGlobalScopes()->find($issue->mediaId);
                if ($item) {
                    CreateAltTextForMediaItem::dispatch($item);
                }

                continue;
            }

            if ($issue->modelClass) {
                GenerateMetaFieldForModel::dispatch(
                    $issue->modelClass,
                    $issue->modelId,
                    $this->fieldForCheck($this->selectedCheck, $issue->field),
                    $issue->missingLocales,
                    $rewrite,
                );
            }
        }

        \Filament\Notifications\Notification::make()
            ->title(__('AI-taken gestart. De resultaten verschijnen zodra de wachtrij ze heeft verwerkt.'))
            ->success()
            ->send();
    }

    public function aiAvailable(?string $checkKey = null): bool
    {
        if ($checkKey === 'missing_alt') {
            return (bool) Ai::default(AiCapability::Vision);
        }

        if (in_array($checkKey, ['missing_meta_title', 'missing_meta_description', 'missing_meta_image', ...self::REWRITE_CHECKS], true)) {
            return (bool) Ai::default(AiCapability::Json);
        }

        return (bool) Ai::default(AiCapability::Json) || (bool) Ai::default(AiCapability::Vision);
    }
}

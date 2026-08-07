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
    protected static string|UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?int $navigationSort = 50;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Content-kwaliteit';

    protected static ?string $title = 'Content-kwaliteit';

    protected static ?string $slug = 'content-quality';

    protected string $view = 'dashed-core::pages.content-quality-dashboard';

    public ?string $selectedCheck = null;

    public array $inlineTarget = [];

    public array $inlineValues = [];

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

    public function editInline(string $checkKey, ?int $mediaId, ?string $modelClass, int|string|null $modelId): void
    {
        $this->inlineTarget = [
            'checkKey' => $checkKey,
            'mediaId' => $mediaId,
            'modelClass' => $modelClass,
            'modelId' => $modelId,
        ];
        $this->inlineValues = [];

        if ($mediaId) {
            $this->inlineValues = ['alt' => ''];

            return;
        }

        // Seed one input per missing locale for the targeted meta field.
        $issue = $this->issues->first(
            fn ($i) => $i->modelClass === $modelClass && (string) $i->modelId === (string) $modelId && $i->checkKey === $checkKey
        );
        foreach (($issue?->missingLocales ?? []) as $locale) {
            $this->inlineValues[$locale] = '';
        }
    }

    public function saveInline(): void
    {
        $target = $this->inlineTarget;

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
                $field = $this->fieldForCheck($target['checkKey']);
                foreach ($this->inlineValues as $locale => $value) {
                    $metadata->setTranslation($field, $locale, trim((string) $value));
                }
                $model->metadata()->save($metadata);
            }
        }

        $this->rescan();
        $this->inlineTarget = [];
        $this->inlineValues = [];
    }

    protected function fieldForCheck(string $checkKey): string
    {
        return match ($checkKey) {
            'missing_meta_title' => 'title',
            'missing_meta_description' => 'description',
            'missing_meta_image' => 'image',
            default => 'title',
        };
    }

    public function aiFix(string $checkKey, ?int $mediaId, ?string $modelClass, int|string|null $modelId): void
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

        $field = $this->fieldForCheck($checkKey);
        $issue = $this->issues->first(
            fn ($i) => $i->modelClass === $modelClass && (string) $i->modelId === (string) $modelId && $i->checkKey === $checkKey
        );
        $missing = $issue?->missingLocales ?? [];

        $generated = app(MetaFieldGenerator::class)->generate($model, $field, $missing);
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

        $field = $this->fieldForCheck($this->selectedCheck);

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
                    $field,
                    $issue->missingLocales,
                );
            }
        }

        \Filament\Notifications\Notification::make()
            ->title('AI-taken gestart. De resultaten verschijnen zodra de wachtrij ze heeft verwerkt.')
            ->success()
            ->send();
    }

    public function aiAvailable(?string $checkKey = null): bool
    {
        if ($checkKey === 'missing_alt') {
            return (bool) Ai::default(AiCapability::Vision);
        }

        if (in_array($checkKey, ['missing_meta_title', 'missing_meta_description', 'missing_meta_image'], true)) {
            return (bool) Ai::default(AiCapability::Json);
        }

        return (bool) Ai::default(AiCapability::Json) || (bool) Ai::default(AiCapability::Vision);
    }
}

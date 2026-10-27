<?php

namespace Dashed\DashedCore\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Livewire\Attributes\Locked;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Dashed\DashedAi\Facades\Ai;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedAi\Enums\AiCapability;
use Dashed\DashedAi\Jobs\CreateAltTextForMediaItem;
use Dashed\DashedCore\ContentQuality\QualityIssue;
use Dashed\DashedCore\ContentQuality\MetaFieldGenerator;
use Dashed\DashedCore\ContentQuality\ContentQualityRegistry;
use Dashed\DashedCore\ContentQuality\ContentQualityScanner;
use RalphJSmit\Filament\MediaLibrary\Models\MediaLibraryItem;
use Dashed\DashedCore\ContentQuality\Jobs\GenerateMetaFieldForModel;

class ContentQualityDashboard extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    protected static string|UnitEnum|null $navigationGroup = 'SEO & site';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Content-kwaliteit';

    protected static ?string $title = 'Content-kwaliteit';

    protected static ?string $slug = 'content-quality';

    protected string $view = 'dashed-core::pages.content-quality-dashboard';

    public ?string $selectedCheck = null;

    /**
     * Wat er inline bewerkt wordt. Alleen editInline() zet dit, na de controle
     * in authorizedIssue(); vanuit de browser is het niet te wijzigen.
     */
    #[Locked]
    public array $inlineTarget = [];

    public array $inlineValues = [];

    /**
     * Checks on an existing meta text: inline editing starts from the current
     * text and "Fix met AI" rewrites it instead of generating a new one.
     */
    public const REWRITE_CHECKS = ['meta_too_long', 'meta_truncated'];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('view_content_quality');
    }

    /**
     * Zoekt het item op in de lijst die de scanner voor de actieve site
     * oplevert, en geeft het alleen terug als de gebruiker het mag bewerken.
     *
     * Alles wat de browser meestuurt (klasse, id, veld, afbeelding) is alleen
     * een zoeksleutel: wat daarna bewerkt wordt komt uit het gevonden item.
     * Zo kan er nooit een model buiten het register, een item van een andere
     * site of een willekeurig metadataveld geraakt worden.
     */
    protected function authorizedIssue(string $checkKey, ?int $mediaId, ?string $modelClass, int|string|null $modelId, ?string $field = null): ?QualityIssue
    {
        $issue = $mediaId
            ? $this->missingAltIssue($checkKey, $mediaId)
            : app(ContentQualityScanner::class)
                ->issues(Sites::getActive(), $checkKey)
                ->first(fn (QualityIssue $i) => $i->mediaId === null
                    && $modelClass !== null
                    && $i->modelClass === $modelClass
                    && (string) $i->modelId === (string) $modelId
                    && ($i->field ?? null) === $field);

        if (! $issue || ! $this->mayEdit($issue)) {
            return null;
        }

        return $issue;
    }

    /**
     * Een afbeelding is alleen te bewerken zolang hij nog geen alt-tekst heeft:
     * precies wat de controle missing_alt meldt. Een bestaande alt-tekst wordt
     * hier dus nooit overschreven. Bewust zonder globale scopes, net als de
     * bibliotheek zelf; afbeeldingen horen niet bij een site.
     */
    protected function missingAltIssue(string $checkKey, int $mediaId): ?QualityIssue
    {
        if ($checkKey !== 'missing_alt' || ! app(ContentQualityRegistry::class)->check($checkKey)) {
            return null;
        }

        $item = MediaLibraryItem::withoutGlobalScopes()->find($mediaId);
        if (! $item || filled($item->alt_text)) {
            return null;
        }

        return new QualityIssue(checkKey: $checkKey, title: (string) ($item->caption ?: '#' . $item->id), mediaId: $item->id);
    }

    protected function mayEdit(QualityIssue $issue): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($issue->mediaId) {
            $item = MediaLibraryItem::withoutGlobalScopes()->find($issue->mediaId);
            if (! $item) {
                return false;
            }

            // De mediabibliotheek kent zelf geen rechten zolang er geen policy
            // is; dan geldt hier hetzelfde als in de bibliotheek.
            return Gate::getPolicyFor(MediaLibraryItem::class) === null
                || $user->can('update', $item);
        }

        $registered = collect(app(ContentQualityRegistry::class)->models())
            ->first(fn ($model) => $model->modelClass === $issue->modelClass);
        if (! $registered) {
            return false;
        }

        $model = $registered->modelClass::find($issue->modelId);
        if (! $model) {
            return false;
        }

        if (method_exists($registered->resourceClass, 'canEdit')) {
            return $registered->resourceClass::canEdit($model);
        }

        return Gate::getPolicyFor($model) !== null && $user->can('update', $model);
    }

    protected function refuse(): void
    {
        \Filament\Notifications\Notification::make()
            ->title(__('Dit item kun je hier niet bewerken.'))
            ->danger()
            ->send();
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
        $this->inlineTarget = [];
        $this->inlineValues = [];

        $issue = $this->authorizedIssue($checkKey, $mediaId, $modelClass, $modelId, $field);
        if (! $issue) {
            $this->refuse();

            return;
        }

        $this->inlineTarget = [
            'checkKey' => $issue->checkKey,
            'mediaId' => $issue->mediaId,
            'modelClass' => $issue->modelClass,
            'modelId' => $issue->modelId,
            'field' => $issue->field,
        ];

        if ($issue->mediaId) {
            $this->inlineValues = ['alt' => ''];

            return;
        }

        // Seed one input per affected locale for the targeted meta field;
        // for length checks start from the current text so it can be trimmed.
        $metadata = in_array($issue->checkKey, self::REWRITE_CHECKS, true)
            ? $issue->modelClass::find($issue->modelId)?->metadata
            : null;
        $metaField = $this->fieldForCheck($issue->checkKey, $issue->field);
        foreach (($issue?->missingLocales ?? []) as $locale) {
            $this->inlineValues[$locale] = $metadata
                ? (string) $metadata->getTranslation($metaField, $locale, false)
                : '';
        }
    }

    public function saveInline(bool $openNext = false): void
    {
        $target = $this->inlineTarget;
        if (! isset($target['checkKey'])) {
            return;
        }

        $position = $this->issues->search(fn ($i) => $this->isTarget($i, $target));

        // Opnieuw toetsen: rechten kunnen ingetrokken zijn sinds het openen.
        $issue = $this->authorizedIssue($target['checkKey'], $target['mediaId'] ?? null, $target['modelClass'] ?? null, $target['modelId'] ?? null, $target['field'] ?? null);
        if (! $issue) {
            $this->inlineTarget = [];
            $this->inlineValues = [];
            $this->refuse();

            return;
        }

        if ($issue->mediaId) {
            $item = MediaLibraryItem::withoutGlobalScopes()->find($issue->mediaId);
            if ($item) {
                $item->alt_text = trim((string) ($this->inlineValues['alt'] ?? ''));
                $item->save();
            }
        } else {
            $model = $issue->modelClass::find($issue->modelId);
            if ($model) {
                $metadata = $model->metadata ?: $model->metadata()->make();
                $field = $this->fieldForCheck($issue->checkKey, $issue->field);
                // Alleen de talen die het item mist; een meegestuurde extra
                // sleutel wordt geen nieuwe vertaling.
                $locales = $issue->missingLocales;
                foreach ($this->inlineValues as $locale => $value) {
                    if (! in_array($locale, $locales, true)) {
                        continue;
                    }
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
        $issue = $this->authorizedIssue($checkKey, $mediaId, $modelClass, $modelId, $field);
        if (! $issue) {
            $this->refuse();

            return;
        }

        if ($issue->mediaId) {
            $item = MediaLibraryItem::withoutGlobalScopes()->find($issue->mediaId);
            if ($item) {
                CreateAltTextForMediaItem::dispatch($item);
            }

            return;
        }

        $model = $issue->modelClass::find($issue->modelId);
        if (! $model) {
            return;
        }

        $field = $this->fieldForCheck($issue->checkKey, $issue->field);
        $missing = $issue->missingLocales;

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
            // Wat de gebruiker niet los mag bewerken, slaat de bulkactie over.
            if (! $this->mayEdit($issue)) {
                continue;
            }

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

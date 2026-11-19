<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Models\Revision;
use Filament\Forms\Components\Textarea;
use Dashed\DashedCore\Classes\Revisions\Revisions;
use Dashed\DashedCore\Filament\Actions\RecordHistoryAction;
use Dashed\DashedCore\Classes\Revisions\ContentApproval;
use Dashed\DashedCore\Classes\Revisions\RevisionSnapshot;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Classes\Actions\TranslateAction;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use Dashed\DashedMarketing\Filament\Actions\RequestSeoAuditAction;
use Dashed\DashedMarketing\Filament\Actions\GenerateMetaForAllLocalesAction;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

trait HasEditableCMSActions
{
    use Translatable {
        fillForm as protected translatableFillForm;
    }

    public function updatingActiveLocale($newVal): void
    {
        $this->oldActiveLocale = $this->activeLocale;
        $this->save();

        $record = $this->getRecord();

        // In de goedkeuringsstroom staat het concept niet in de database. De
        // nieuwe taal wordt dan gelezen met het concept tijdelijk toegepast.
        if (Revisions::supports($record) && ContentApproval::savesAsDraft($record) && ($draft = ContentApproval::openDraft($record))) {
            $this->withSnapshotApplied($draft->snapshot, fn () => $this->fillLocaleFromRecord($newVal));

            return;
        }

        $this->fillLocaleFromRecord($newVal);
    }

    protected function fillLocaleFromRecord($newVal): void
    {
        foreach ($this->getRecord()->resourceRelations ?? [] as $resourceRelation => $relationClass) {
            foreach ($this->data[$resourceRelation] ?? [] as $key => $relationArray) {
                $relation = $this->getRecord()->$resourceRelation()->find($relationArray['id'] ?? 0);
                if ($relation) {
                    foreach ($relation->translatable as $attribute) {
                        $this->data[$resourceRelation][$key][$attribute] = $relation->getTranslation($attribute, $newVal);
                    }

                    foreach ($relationClass['childRelations'] ?? [] as $childRelationName) {
                        foreach ($this->data[$resourceRelation][$key][$childRelationName] ?? [] as $childKey => $childRelationArray) {
                            $childRelation = $relation->$childRelationName()->find($childRelationArray['id'] ?? 0);
                            if ($childRelation) {
                                foreach ($childRelation->translatable as $childAttribute) {
                                    $this->data[$resourceRelation][$key][$childRelationName][$childKey][$childAttribute] = $childRelation->getTranslation($childAttribute, $newVal);
                                }
                            }
                        }
                    }
                }
            }
        }

        if (method_exists($this->getRecord(), 'customBlocks')) {
            $this->data['customBlocks'] = $this->getRecord()->customBlocks ? $this->getRecord()->customBlocks->getTranslation('blocks', $newVal) : null;
        }

        if (method_exists($this->getRecord(), 'metadata')) {
            foreach ($this->getRecord()->metadata->getTranslatableAttributes() as $attribute) {
                $this->data['metadata'][$attribute] = $this->getRecord()->metadata ? $this->getRecord()->metadata->getTranslation($attribute, $newVal) : null;
            }
        }

        if (method_exists(self::class, 'mutateFormDataBeforeFill')) {
            $this->data = $this->mutateFormDataBeforeFill($this->data);
        }
    }

    public function CMSActions(): array
    {
        $actions = [];

        if (method_exists($this->record, 'getUrl')) {
            if (count(Locales::getLocalesArray()) > 1) {
                $viewActions = [];

                foreach (Locales::getLocales() as $locale) {
                    $viewActions[] = Action::make('view')
                        ->button()
                        ->label($locale['native'])
                        ->url($this->record->getUrl($locale['id']))
                        ->openUrlInNewTab();
                }

                if (count($viewActions)) {
                    $actions[] = ActionGroup::make($viewActions)
                        ->label(__('Bekijk'))
                        ->icon('heroicon-o-eye')
                        ->button();
                }
            } else {
                $actions[] = Action::make('view')
                    ->button()
                    ->label(__('Bekijk'))
                    ->icon('heroicon-o-eye')
                    ->url($this->record->getUrl($this->activeLocale))
                    ->openUrlInNewTab();
            }
        }

        // In de goedkeuringsstroom gaan wijzigingen van een redacteur via het
        // concept. Acties die rechtstreeks in het live record schrijven horen
        // daar niet bij.
        $writesLive = ! (Revisions::supports($this->record) && ContentApproval::savesAsDraft($this->record));

        array_push($actions, ...$this->approvalActions());

        // Voorvertoning, linksuggesties en sjabloon opslaan schrijven niet
        // live: die werken op de formulierstaat of lezen het opgeslagen record.
        $actions = array_merge($actions, \Dashed\DashedCore\Filament\Actions\Editor\EditorActions::header());

        $groupedActions = \Dashed\DashedCore\Filament\Actions\Editor\EditorActions::grouped();

        if (method_exists($this->record, 'getUrl') && $writesLive) {
            $groupedActions[] = Action::make('insertTemplateBlock')
                ->label(__('Template blok invoegen'))
                ->visible(GlobalBlock::count() > 0)
                ->schema([
                    Select::make('templateBlock')
                        ->options(GlobalBlock::all()->mapWithKeys(fn ($block) => [$block->id => $block->name]))
                        ->required()
                        ->preload()
                        ->searchable()
                        ->label(__('Template blok'))
                        ->helperText(__('Let op: dit refreshed de pagina, sla dus eerst op!')),
                ])
                ->action(function (array $data) {
                    $globalBlock = GlobalBlock::find($data['templateBlock']);
                    $this->record->setTranslation('content', $this->activeLocale, array_merge($this->record->getTranslation('content', $this->activeLocale) ?: [], $globalBlock->getTranslation('content', $this->activeLocale) ?: []));
                    $this->record->save();

                    Notification::make()
                        ->title(__('Template blok is toegevoegd'))
                        ->success()
                        ->send();

                    return redirect()->to(request()->header('Referer'));
                })
                ->icon('heroicon-o-globe-alt')
                ->color('primary');
        }

        $groupedActions[] = Action::make('Dupliceer')
            ->action('duplicate')
            ->icon('heroicon-o-document-duplicate')
            ->color('warning');

        if (class_exists(RequestSeoAuditAction::class)) {
            $groupedActions[] = RequestSeoAuditAction::make();
        }

        if (class_exists(GenerateMetaForAllLocalesAction::class)) {
            $groupedActions[] = GenerateMetaForAllLocalesAction::make();
        }

        if (method_exists($this->record, 'getUrl') && class_exists(\Dashed\DashedSeo\Filament\Actions\SearchConsoleKeywordsAction::class)) {
            $groupedActions[] = \Dashed\DashedSeo\Filament\Actions\SearchConsoleKeywordsAction::make();
        }

        if ($writesLive) {
            $groupedActions[] = self::translateAction();
            $groupedActions[] = self::copyAction();
            // Alleen zichtbaar voor modellen met velden in ChangeFields en voor
            // wie het record mag bewerken. Binnen $writesLive: een geplande
            // wijziging schrijft later live en zou de goedkeuring omzeilen.
            $groupedActions[] = \Dashed\DashedCore\Filament\Actions\ScheduleChangeAction::make();
        }

        $actions[] = ActionGroup::make($groupedActions)
            ->label(__('Acties'))
            ->icon('heroicon-o-ellipsis-vertical')
            ->button();

        return array_merge($actions, [
            RecordHistoryAction::make(),
            LocaleSwitcher::make()
                ->icon('heroicon-o-language'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ]);
    }

    public function viewAction(): ActionGroup|Action
    {
        if (count(Locales::getLocalesArray()) > 1) {
            $viewActions = [];

            foreach (Locales::getLocales() as $locale) {
                $viewActions[] = Action::make('view')
                    ->button()
                    ->label($locale['native'])
                    ->url($this->record->getUrl($locale['id']))
                    ->openUrlInNewTab();
            }

            return ActionGroup::make($viewActions)
                ->label(__('Bekijk'))
                ->icon('heroicon-o-eye')
                ->button();
        } else {
            return Action::make('view')
                ->button()
                ->label(__('Bekijk'))
                ->icon('heroicon-o-eye')
                ->url($this->record->getUrl($this->activeLocale))
                ->openUrlInNewTab();
        }
    }

    public function translateAction(): Action
    {
        return Action::make('translate')
            ->icon('heroicon-m-language')
            ->label(__('Vertaal'))
            ->visible(AutomatedTranslation::automatedTranslationsEnabled())
            ->schema([
                Select::make('to_locales')
                    ->options(Locales::getLocalesArray())
                    ->preload()
                    ->searchable()
                    ->default(fn ($livewire) => collect(Locales::getLocalesArrayWithoutCurrent($livewire->activeLocale))->keys()->toArray())
                    ->required()
                    ->label(__('Naar talen'))
                    ->multiple(),
            ])
            ->action(function (array $data) {
                TranslateAction::startTranslation($this->record, $this->activeLocale, $data['to_locales']);

                Notification::make()
                    ->title(__('Item wordt vertaald, dit kan even duren. Sla de pagina niet op tot de vertalingen klaar zijn.'))
                    ->warning()
                    ->send();

                return redirect()->to(request()->header('Referer'));
            });
    }

    public function copyAction(): Action
    {
        return Action::make('copy')
            ->icon('heroicon-o-document-duplicate')
            ->label(__('Kopiëren'))
            ->visible(count(Locales::getLocalesArray()) > 1)
            ->schema([
                TextEntry::make('description')
                    ->state('Hiermee kopieer je alle inhoudt naar andere talen. Dit kan even duren.'),
                Select::make('to_locales')
                    ->options(Locales::getLocalesArray())
                    ->preload()
                    ->searchable()
                    ->default(fn ($livewire) => collect(Locales::getLocalesArrayWithoutCurrent($livewire->activeLocale))->keys()->toArray())
                    ->required()
                    ->label(__('Naar talen'))
                    ->multiple(),
            ])
            ->action(function (array $data) {

                foreach ($this->record->translatable as $column) {
                    $textToTranslate = $this->record->getTranslation($column, $this->activeLocale);
                    foreach ($data['to_locales'] as $locale) {
                        $this->record->setTranslation($column, $locale, $textToTranslate);
                    }
                }

                $this->record->save();

                if ($this->record->metadata) {
                    $translatableMetaColumns = [
                        'title',
                        'description',
                    ];

                    foreach ($translatableMetaColumns as $column) {
                        $textToTranslate = $this->record->metadata->getTranslation($column, $this->activeLocale);
                        foreach ($data['to_locales'] as $locale) {
                            $this->record->metadata->setTranslation($column, $locale, $textToTranslate);
                        }
                    }

                    $this->record->metadata->save();
                }

                if ($this->record->customBlocks) {
                    $translatableCustomBlockColumns = [
                        'blocks',
                    ];

                    foreach ($translatableCustomBlockColumns as $column) {
                        $textToTranslate = $this->record->customBlocks->getTranslation($column, $this->activeLocale);
                        foreach ($data['to_locales'] as $locale) {
                            $this->record->customBlocks->setTranslation($column, $locale, $textToTranslate);
                        }
                    }

                    $this->record->customBlocks->save();
                }

                Notification::make()
                    ->title(__('Item is gekopieerd naar andere talen'))
                    ->success()
                    ->send();

                return redirect()->to(request()->header('Referer'));
            });
    }

    public function duplicate()
    {
        $newModel = $this->record->replicate();

        if (in_array('slug', $this->record->translatable)) {
            foreach (Locales::getLocales() as $locale) {
                $newModel->setTranslation('slug', $locale['id'], \Dashed\DashedCore\Classes\Seo\SlugMaker::uniek(
                    (string) $newModel->getTranslation('slug', $locale['id'], false),
                    fn (string $kandidaat) => $this->record::class::where('slug->'.$locale['id'], $kandidaat)->exists(),
                ));
            }
        }

        $newModel->save();

        if ($this->record->customBlocks) {
            $newCustomBlock = $this->record->customBlocks->replicate();
            $newCustomBlock->blockable_id = $newModel->id;
            $newCustomBlock->save();
        }

        if ($this->record->metaData) {
            $newMetaData = $this->record->metaData->replicate();
            $newMetaData->metadatable_id = $newModel->id;
            $newMetaData->save();
        }

        return redirect(self::getUrl(['record' => $newModel]));
    }

    private function removeUUIDKeys($array)
    {
        $new_array = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $key)) {
                    $new_array[] = self::removeUUIDKeys($value);
                } else {
                    $new_array[$key] = self::removeUUIDKeys($value);
                }
            } else {
                if (! preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $key)) {
                    $new_array[$key] = $value;
                } else {
                    $new_array[] = $value;
                }
            }
        }

        return $new_array;
    }

    /**
     * Zelfde als LaraZeus' Translatable::handleRecordUpdate(), zonder de
     * `$this->form->fill($this->form->getState())` per andere taal. Die
     * valideerde opnieuw de actieve formulierdata, die save() op dat moment
     * al gevalideerd had, en hydrateerde daarna het hele formulier opnieuw.
     * Op een pagina met veel blokken was dat per extra taal de helft van de
     * opslaantijd, en elk builder-blok kreeg een nieuwe sleutel, waardoor
     * open blokken na opslaan weer dichtklapten.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $record->fill(Arr::except($data, $translatableAttributes));

        foreach (Arr::only($data, $translatableAttributes) as $key => $value) {
            $record->setTranslation($key, $this->activeLocale, $value);
        }

        foreach ($this->otherLocaleData as $locale => $localeData) {
            $localeData = $this->mutateFormDataBeforeSave($localeData);

            foreach (Arr::only($localeData, $translatableAttributes) as $key => $value) {
                $record->setTranslation($key, $locale, $value);
            }
        }

        $record->save();

        return $record;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        // Save order for content blocks
        if ($data['content'] ?? false) {
            // otherLocaleData komt hier langs zonder getState(), dus zonder
            // dat de builder zijn markering voor ongemoeide blokken weghaalt.
            $data['content'] = self::removeUUIDKeys(
                \Dashed\DashedCore\Filament\Components\ContentBuilder::withoutUntouchedMarkers($data['content']),
            );
        }

        return parent::mutateFormDataBeforeSave($data); // TODO: Change the autogenerated stub
    }

    /**
     * Staat er een open concept (goedkeuringsstroom aan), dan opent het
     * formulier op het concept en niet op de live versie.
     */
    protected function fillForm(): void
    {
        $record = $this->getRecord();
        $draft = Revisions::supports($record) && ContentApproval::enabledFor($record)
            ? ContentApproval::openDraft($record)
            : null;

        if (! $draft) {
            $this->translatableFillForm();

            return;
        }

        $this->withSnapshotApplied($draft->snapshot, fn () => $this->translatableFillForm());

        $mayPublish = ContentApproval::mayPublish(auth()->user());

        Notification::make()
            ->title($draft->status === Revision::STATUS_PENDING ? __('Je bekijkt een concept dat ter beoordeling ligt') : __('Je bewerkt een concept'))
            ->body($mayPublish
                ? __('De live versie is ongewijzigd. Opslaan publiceert dit concept direct; Goedkeuren doet dat met een beoordeling erbij.')
                : __('De live versie is ongewijzigd tot het concept is goedgekeurd.'))
            ->warning()
            ->send();
    }

    /**
     * Elke opslag via deze pagina legt een revisie vast (de databasestand na
     * het opslaan, niet de formulierstaat). In de goedkeuringsstroom slaat een
     * redacteur zonder publiceerrecht op in het concept in plaats van live.
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $record = $this->getRecord();

        if (! Revisions::supports($record)) {
            parent::save($shouldRedirect, $shouldSendSavedNotification);

            return;
        }

        if (ContentApproval::savesAsDraft($record)) {
            $this->saveAsDraft($shouldSendSavedNotification);

            return;
        }

        rescue(fn () => Revisions::ensureBaseline($record));

        parent::save($shouldRedirect, $shouldSendSavedNotification);

        rescue(function () use ($record) {
            $revision = Revisions::record($record, 'save', auth()->user());

            if ($revision && ContentApproval::enabledFor($record)) {
                ContentApproval::supersedeOpenDrafts($record);
            }
        });
    }

    /**
     * De gewone opslag van Filament, maar in een transactie die na het
     * vastleggen van de opname wordt teruggedraaid. Het open concept wordt
     * eerst toegepast, zodat talen en relaties die nu niet in het formulier
     * staan uit het concept komen en niet uit de live versie.
     */
    protected function saveAsDraft(bool $shouldSendSavedNotification = true): void
    {
        $record = $this->getRecord();
        $draft = ContentApproval::openDraft($record);

        if (! $draft) {
            Revisions::ensureBaseline($record);
        }

        $snapshot = $this->withSnapshotApplied($draft?->snapshot, function () use ($record) {
            parent::save(false, false);

            return RevisionSnapshot::take($record);
        });

        $stored = ContentApproval::storeDraft($record, $snapshot, auth()->user());

        if ($shouldSendSavedNotification) {
            Notification::make()
                ->title($stored ? __('Opgeslagen als concept') : __('Geen wijzigingen ten opzichte van de live versie'))
                ->body($stored ? __('Bied het concept aan ter beoordeling om het te publiceren.') : null)
                ->success()
                ->send();
        }
    }

    /**
     * Voert $callback uit met de opname in de database toegepast, en draait
     * dat daarna terug. Het model in het geheugen houdt de toegepaste stand.
     */
    protected function withSnapshotApplied(?array $snapshot, callable $callback): mixed
    {
        $record = $this->getRecord();
        $connection = $record->getConnection();
        $connection->beginTransaction();

        try {
            if ($snapshot) {
                RevisionSnapshot::apply($record, $snapshot);
            }

            return $callback();
        } finally {
            $connection->rollBack();
        }
    }

    /**
     * @return array<int, Action>
     */
    public function approvalActions(): array
    {
        return [
            Action::make('approvalStatus')
                ->label(fn () => ContentApproval::openDraft($this->getRecord())?->statusLabel())
                ->badge()
                ->color(fn () => ContentApproval::openDraft($this->getRecord())?->status === Revision::STATUS_PENDING ? 'warning' : 'gray')
                ->disabled()
                ->visible(fn () => ContentApproval::enabledFor($this->getRecord()) && ContentApproval::openDraft($this->getRecord()) !== null),

            Action::make('submitForReview')
                ->label(__('Ter beoordeling aanbieden'))
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(__('Je wijzigingen worden eerst als concept opgeslagen. Een beoordelaar krijgt een melding.'))
                ->visible(fn () => ContentApproval::savesAsDraft($this->getRecord())
                    && ContentApproval::openDraft($this->getRecord())?->status !== Revision::STATUS_PENDING)
                ->action(function () {
                    $this->save(false, false);
                    $draft = ContentApproval::openDraft($this->getRecord());

                    if (! $draft) {
                        Notification::make()->title(__('Er is niets om aan te bieden: het concept is gelijk aan de live versie.'))->warning()->send();

                        return;
                    }

                    ContentApproval::submit($draft, auth()->user());

                    Notification::make()->title(__('Ter beoordeling aangeboden'))->success()->send();
                }),

            Action::make('approveDraft')
                ->label(__('Goedkeuren'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => ContentApproval::enabledFor($this->getRecord())
                    && ContentApproval::mayPublish(auth()->user())
                    && ContentApproval::openDraft($this->getRecord()) !== null)
                ->modalHeading(__('Concept goedkeuren en publiceren'))
                ->modalDescription(function () {
                    $draft = ContentApproval::openDraft($this->getRecord());
                    $live = Revisions::latestLive($this->getRecord());
                    $text = __('De opgeslagen versie van het concept gaat live. Wijzigingen die je hier nog niet hebt opgeslagen gaan niet mee.');

                    if ($draft && $live && $draft->base_revision_id && $live->id !== $draft->base_revision_id) {
                        $text .= ' ' . __('Let op: de live versie is gewijzigd nadat dit concept is begonnen. Goedkeuren overschrijft die wijzigingen.');
                    }

                    return $text;
                })
                ->schema([
                    Textarea::make('comment')
                        ->label(__('Opmerking (optioneel)'))
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $draft = ContentApproval::openDraft($this->getRecord());

                    if ($draft) {
                        ContentApproval::approve($draft, auth()->user(), $data['comment'] ?? null);
                    }

                    Notification::make()->title(__('Goedgekeurd en gepubliceerd'))->success()->send();

                    return redirect(static::getUrl(['record' => $this->getRecord()]));
                }),

            Action::make('rejectDraft')
                ->label(__('Afkeuren'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ContentApproval::enabledFor($this->getRecord())
                    && ContentApproval::mayPublish(auth()->user())
                    && ContentApproval::openDraft($this->getRecord())?->status === Revision::STATUS_PENDING)
                ->modalHeading(__('Concept afkeuren'))
                ->schema([
                    Textarea::make('comment')
                        ->label(__('Wat moet er anders?'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    $draft = ContentApproval::openDraft($this->getRecord());

                    if ($draft) {
                        ContentApproval::reject($draft, auth()->user(), $data['comment']);
                    }

                    Notification::make()->title(__('Afgekeurd, de redacteur krijgt een melding'))->success()->send();
                }),

            Action::make('discardDraft')
                ->label(__('Concept verwerpen'))
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('Het concept wordt weggegooid en het formulier toont weer de live versie.'))
                ->visible(fn () => ContentApproval::enabledFor($this->getRecord()) && ContentApproval::openDraft($this->getRecord()) !== null)
                ->action(function () {
                    if ($draft = ContentApproval::openDraft($this->getRecord())) {
                        ContentApproval::discard($draft);
                    }

                    return redirect(static::getUrl(['record' => $this->getRecord()]));
                }),
        ];
    }

    public function versionsAction(): Action
    {
        return RecordHistoryAction::make();
    }
}

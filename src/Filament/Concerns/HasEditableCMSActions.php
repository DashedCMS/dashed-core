<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Infolists\Components\TextEntry;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCore\Filament\Actions\ShowSEOScoreAction;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

trait HasEditableCMSActions
{
    use Translatable;

    public function updatingActiveLocale($newVal): void
    {
        $this->oldActiveLocale = $this->activeLocale;
        $this->save();

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
                                    //                                    dd($this->data, $resourceRelation, $key, $childRelationName, $childKey, $childAttribute, $childRelation->getTranslation($childAttribute, $newVal));
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
                        ->label('Bekijk')
                        ->icon('heroicon-o-eye')
                        ->button();
                }
            } else {
                $actions[] = Action::make('view')
                    ->button()
                    ->label('Bekijk')
                    ->icon('heroicon-o-eye')
                    ->url($this->record->getUrl($this->activeLocale))
                    ->openUrlInNewTab();
            }
        }

        if (method_exists($this->record, 'getUrl')) {
            $actions[] = Action::make('insertTemplateBlock')
                ->label('Template blok invoegen')
                ->visible(GlobalBlock::count() > 0)
                ->schema([
                    Select::make('templateBlock')
                        ->options(GlobalBlock::all()->mapWithKeys(fn ($block) => [$block->id => $block->name]))
                        ->required()
                        ->preload()
                        ->searchable()
                        ->label('Template blok')
                        ->helperText('Let op: dit refreshed de pagina, sla dus eerst op!'),
                ])
                ->action(function (array $data) {
                    $globalBlock = GlobalBlock::find($data['templateBlock']);
                    $this->record->setTranslation('content', $this->activeLocale, array_merge($this->record->getTranslation('content', $this->activeLocale) ?: [], $globalBlock->getTranslation('content', $this->activeLocale) ?: []));
                    $this->record->save();

                    Notification::make()
                        ->title('Template blok is toegevoegd')
                        ->success()
                        ->send();

                    return redirect()->to(request()->header('Referer'));
                })
                ->icon('heroicon-o-globe-alt')
                ->color('primary');
        }

        $actions[] = Action::make('Dupliceer')
            ->action('duplicate')
            ->icon('heroicon-o-document-duplicate')
            ->color('warning');

        //        if (method_exists($this->record, 'getUrl')) {
        //            $actions[] = ShowSEOScoreAction::make();
        //        }

        return array_merge($actions, [
            self::translateAction(),
            self::copyAction(),
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
                ->label('Bekijk')
                ->icon('heroicon-o-eye')
                ->button();
        } else {
            return Action::make('view')
                ->button()
                ->label('Bekijk')
                ->icon('heroicon-o-eye')
                ->url($this->record->getUrl($this->activeLocale))
                ->openUrlInNewTab();
        }
    }

    public function translateAction(): Action
    {
        return Action::make('translate')
            ->icon('heroicon-m-language')
            ->label('Vertaal')
            ->visible(AutomatedTranslation::automatedTranslationsEnabled())
            ->schema([
                Select::make('to_locales')
                    ->options(Locales::getLocalesArray())
                    ->preload()
                    ->searchable()
                    ->default(collect(Locales::getLocalesArrayWithoutCurrent())->keys()->toArray())
                    ->required()
                    ->label('Naar talen')
                    ->multiple(),
            ])
            ->action(function (array $data) {
                AutomatedTranslation::translateModel($this->record, $this->activeLocale, $data['to_locales']);

                Notification::make()
                    ->title('Item wordt vertaald, dit kan even duren. Sla de pagina niet op tot de vertalingen klaar zijn.')
                    ->warning()
                    ->send();

                return redirect()->to(request()->header('Referer'));
            });
    }

    public function copyAction(): Action
    {
        return Action::make('copy')
            ->icon('heroicon-o-document-duplicate')
            ->label('Kopiëren')
            ->visible(count(Locales::getLocalesArray()) > 1)
            ->schema([
                TextEntry::make('description')
                    ->state('Hiermee kopieer je alle inhoudt naar andere talen. Dit kan even duren.'),
                Select::make('to_locales')
                    ->options(Locales::getLocalesArray())
                    ->preload()
                    ->searchable()
                    ->default(collect(Locales::getLocalesArrayWithoutCurrent())->keys()->toArray())
                    ->required()
                    ->label('Naar talen')
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
                    ->title('Item is gekopieerd naar andere talen')
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
                $newModel->setTranslation('slug', $locale['id'], $newModel->getTranslation('slug', $locale['id']));
                while ($this->record::class::where('slug->' . $locale['id'], $newModel->getTranslation('slug', $locale['id']))->count()) {
                    $newModel->setTranslation('slug', $locale['id'], $newModel->getTranslation('slug', $locale['id']) . Str::random(1));
                }
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

    public function mutateFormDataBeforeSave(array $data): array
    {
        //Save order for content blocks
        if ($data['content'] ?? false) {
            $data['content'] = self::removeUUIDKeys($data['content']);
        }

        return parent::mutateFormDataBeforeSave($data); // TODO: Change the autogenerated stub
    }
}

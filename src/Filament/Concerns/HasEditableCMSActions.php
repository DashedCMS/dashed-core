<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Filament\Actions\ShowSEOScoreAction;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use Dashed\DashedTranslations\Jobs\TranslateValueFromModel;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\LocaleSwitcher;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;
use Illuminate\Support\Str;

trait HasEditableCMSActions
{
    use Translatable;

    public function updatingActiveLocale($newVal): void
    {
        $this->oldActiveLocale = $this->activeLocale;
        $this->save();

        if (method_exists($this->getRecord(), 'customBlocks')) {
            $this->data['customBlocks'] = $this->getRecord()->customBlocks->getTranslation('blocks', $newVal);
        }

        if (method_exists($this->getRecord(), 'metadata')) {
            foreach ($this->getRecord()->metadata->getTranslatableAttributes() as $attribute) {
                $this->data['metadata'][$attribute] = $this->getRecord()->metadata->getTranslation($attribute, $newVal);
            }
        }
    }

    public function CMSActions(): array
    {
        return [
            Action::make('view')
                ->button()
                ->label('Bekijk')
                ->url($this->record->getUrl())
                ->openUrlInNewTab(),
            Action::make('Dupliceer')
                ->action('duplicate')
                ->color('warning'),
            ShowSEOScoreAction::make(),
            Action::make('translate')
                ->icon('heroicon-m-language')
                ->label('Vertaal')
                ->visible(AutomatedTranslation::automatedTranslationsEnabled())
                ->form([
                    Select::make('to_locales')
                        ->options(Locales::getLocalesArray())
                        ->preload()
                        ->searchable()
                        ->default(collect(Locales::getLocalesArrayWithoutCurrent())->keys()->toArray())
                        ->required()
                        ->label('Naar talen')
                        ->multiple()
                ])
                ->action(function (array $data) {
                    foreach ($this->record->translatable as $column) {
                        if (!method_exists($this->record, $column)) {
                            $textToTranslate = $this->record->getTranslation($column, $this->activeLocale);

                            foreach ($data['to_locales'] as $locale) {
                                TranslateValueFromModel::dispatch($this->record, $column, $textToTranslate, $locale, $this->activeLocale);
                            }
                        }
                    }

                    if ($this->record->metadata) {
                        $translatableMetaColumns = [
                            'title',
                            'description',
                        ];

                        foreach ($translatableMetaColumns as $column) {
                            $textToTranslate = $this->record->metadata->getTranslation($column, $this->activeLocale);
                            foreach ($data['to_locales'] as $locale) {
                                TranslateValueFromModel::dispatch($this->record->metadata, $column, $textToTranslate, $locale, $this->activeLocale);
                            }
                        }
                    }

                    if ($this->record->customBlocks) {
                        $translatableCustomBlockColumns = [
                            'blocks',
                        ];

                        foreach ($translatableCustomBlockColumns as $column) {
                            $textToTranslate = $this->record->customBlocks->getTranslation($column, $this->activeLocale);
                            foreach ($data['to_locales'] as $locale) {
                                TranslateValueFromModel::dispatch($this->record->customBlocks, $column, $textToTranslate, $locale, $this->activeLocale, [
                                    'customBlock' => str($this->record::class . 'Blocks')->explode('\\')->last(),
                                ]);
                            }
                        }
                    }

                    //Refresh page to make sure saving does not overwrite the translation anymore
                    Notification::make()
                        ->title("Item wordt vertaald, dit kan even duren. Sla de pagina niet op tot de vertalingen klaar zijn.")
                        ->success()
                        ->send();

                    return redirect()->to(request()->header('Referer'));
                }),
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    public function duplicate()
    {
        $newModel = $this->record->replicate();
        foreach (Locales::getLocales() as $locale) {
            $newModel->setTranslation('slug', $locale['id'], $newModel->getTranslation('slug', $locale['id']));
            while ($this->record::class::where('slug->' . $locale['id'], $newModel->getTranslation('slug', $locale['id']))->count()) {
                $newModel->setTranslation('slug', $locale['id'], $newModel->getTranslation('slug', $locale['id']) . Str::random(1));
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
}

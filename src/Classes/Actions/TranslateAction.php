<?php

namespace Dashed\DashedCore\Classes\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use Dashed\DashedTranslations\Classes\Translatables\TranslationStarter;

class TranslateAction
{
    public static function make(): Action
    {
        return Action::make('translate')
            ->icon('heroicon-o-language')
            ->label(__('Vertaal'))
            ->accessSelectedRecords()
            ->deselectRecordsAfterCompletion()
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
            ->action(function (Collection $records, array $data, $livewire) {
                foreach ($records as $record) {
                    self::startTranslation($record, $livewire->activeLocale, $data['to_locales']);
                }

                Notification::make()
                    ->title(__('Items worden vertaald, dit kan even duren.'))
                    ->warning()
                    ->send();
            });
    }

    /**
     * Start een vertaling van het model plus elk geregistreerd kind dat in
     * zijn vingerafdruk meetelt (de opties van een filter of extra, de velden
     * van een formulier). translateModel() alleen stuurt die kinderen niet
     * mee, en dan wordt de vertaalstatus van de ouder nooit compleet. Met een
     * oudere dashed-translations zonder TranslationStarter blijft het oude
     * gedrag staan.
     *
     * @param  array<int, string>  $toLocales
     */
    public static function startTranslation(Model $record, string $fromLocale, array $toLocales): void
    {
        if (class_exists(TranslationStarter::class)) {
            TranslationStarter::start($record, $fromLocale, $toLocales);

            return;
        }

        AutomatedTranslation::translateModel($record, $fromLocale, $toLocales);
    }
}

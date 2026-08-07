<?php

namespace Dashed\DashedCore\Classes\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;

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
                    AutomatedTranslation::translateModel($record, $livewire->activeLocale, $data['to_locales']);
                }

                Notification::make()
                    ->title(__('Items worden vertaald, dit kan even duren.'))
                    ->warning()
                    ->send();
            });
    }
}

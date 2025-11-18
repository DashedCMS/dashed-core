<?php

namespace Dashed\DashedCore\Classes\Actions;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class TranslateAction
{
    public static function make(): Action
    {
        return Action::make('translate')
            ->icon('heroicon-o-language')
            ->label('Vertaal')
            ->accessSelectedRecords()
            ->deselectRecordsAfterCompletion()
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
            ->action(function (Collection $records, array $data, $livewire) {
                foreach ($records as $record) {
                    AutomatedTranslation::translateModel($record, $livewire->activeLocale, $data['to_locales']);
                }

                Notification::make()
                    ->title('Items worden vertaald, dit kan even duren.')
                    ->warning()
                    ->send();
            });
    }
}

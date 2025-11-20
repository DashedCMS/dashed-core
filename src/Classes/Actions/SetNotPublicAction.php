<?php

namespace Dashed\DashedCore\Classes\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class SetNotPublicAction
{
    public static function make(): Action
    {
        return Action::make('setNotPublic')
            ->icon('heroicon-o-eye-slash')
            ->label('Zet naar niet openbaar')
            ->accessSelectedRecords()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                foreach ($records as $record) {
                    $record->public = false;
                    $record->save();
                }

                Notification::make()
                    ->title('De geselecteerde items zijn nu niet meer openbaar.')
                    ->success()
                    ->send();
            });
    }
}

<?php

namespace Dashed\DashedCore\Classes\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class SetPublicAction
{
    public static function make(): Action
    {
        return Action::make('setPublic')
            ->icon('heroicon-o-eye')
            ->label('Zet naar openbaar')
            ->accessSelectedRecords()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                foreach ($records as $record) {
                    $record->public = true;
                    $record->save();
                }

                Notification::make()
                    ->title('De geselecteerde items zijn nu openbaar.')
                    ->success()
                    ->send();
            });
    }
}

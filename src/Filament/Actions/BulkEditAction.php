<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Html;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\BulkEditRun;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\Utilities\Get;
use Dashed\DashedCore\Classes\Changes\ChangeSet;
use Dashed\DashedCore\Classes\Changes\BulkEditor;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Dashed\DashedCore\Filament\Resources\BulkEditRunResource;

/**
 * Bulkactie "Bewerken": velden kiezen, voorbeeld bekijken (oud en nieuw per
 * record), uitvoeren. Elke run is ongedaan te maken vanuit het overzicht
 * Bulkbewerkingen. Bruikbaar op elke lijst van een model dat velden heeft
 * aangemeld bij ChangeFields.
 */
class BulkEditAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('bulkEdit')
            ->label(__('Bewerken'))
            ->icon('heroicon-o-pencil-square')
            ->authorizeIndividualRecords('update')
            ->modalHeading(__('Selectie bewerken'))
            ->modalSubmitActionLabel(__('Uitvoeren'))
            ->steps(function (BulkAction $action, Collection $records) {
                $modelClass = (string) $action->getModel();
                $fields = ChangeFields::for($modelClass, 'bulk');

                return [
                    Step::make(__('Wijzigingen'))
                        ->schema(ChangeSet::schema($fields, $modelClass)),
                    Step::make(__('Voorbeeld'))
                        ->schema([
                            Html::make(function (Get $get) use ($fields, $modelClass, $records) {
                                $data = [];

                                foreach (array_keys($fields) as $key) {
                                    $data['op_' . $key] = $get('op_' . $key);
                                    $data['value_' . $key] = $get('value_' . $key);
                                }

                                return BulkEditor::previewHtml($modelClass, $records->take(BulkEditor::PREVIEW_SAMPLE), ChangeSet::fromForm($fields, $data), app()->getLocale(), $records->count());
                            }),
                        ]),
                ];
            })
            ->action(function (Collection $records, array $data, BulkAction $action) {
                $modelClass = (string) $action->getModel();
                $changes = ChangeSet::fromForm(ChangeFields::for($modelClass, 'bulk'), $data);

                if (! $changes) {
                    Notification::make()->title(__('Kies minstens één wijziging'))->danger()->send();
                    $action->halt();

                    return;
                }

                $run = BulkEditor::start($modelClass, $records->modelKeys(), $changes, app()->getLocale(), auth()->user());

                static::notify($run);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function notify(BulkEditRun $run): void
    {
        $overview = rescue(fn () => [Action::make('runs')->label(__('Ongedaan maken'))->url(BulkEditRunResource::getUrl())], [], false);

        if ($run->status === BulkEditRun::STATUS_QUEUED) {
            Notification::make()
                ->title(__('Bulkbewerking gestart'))
                ->body(__('De :aantal records worden op de achtergrond bijgewerkt. Je ziet de voortgang bij het takenicoon.', ['aantal' => $run->total]))
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title(__(':gewijzigd van :totaal records gewijzigd', ['gewijzigd' => $run->changed, 'totaal' => $run->total]))
            ->body($run->failed ? __(':aantal mislukt: :fout', ['aantal' => $run->failed, 'fout' => $run->error]) : null)
            ->status($run->failed ? 'warning' : 'success')
            ->actions($run->changed ? $overview : [])
            ->send();
    }
}

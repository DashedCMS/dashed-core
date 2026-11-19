<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Revisions\Revisions;
use Dashed\DashedCore\Classes\Revisions\ActivityTimeline;

/**
 * "Versies" op een bewerkpagina: revisies (vergelijken, terugzetten) en de
 * wijzigingen uit de activitylog in één slide-over. Op elke pagina met
 * HasEditableCMSActions zit hij al in CMSActions(); een andere bewerkpagina
 * doet mee met één regel in getHeaderActions(): `RecordHistoryAction::make()`.
 * Zonder revisies (een pagina zonder HasEditableCMSActions) toont hij alleen
 * de wijzigingen, mits het model LogsActivity gebruikt.
 */
class RecordHistoryAction
{
    public static function make(string $name = 'recordHistory'): Action
    {
        return Action::make($name)
            ->label(fn ($livewire) => Revisions::supports(static::recordOf($livewire)) && static::hasRevisionsFlow($livewire) ? __('Versies') : __('Wijzigingen'))
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading(fn ($livewire) => static::hasRevisionsFlow($livewire) ? __('Versies en wijzigingen') : __('Wijzigingen'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Sluiten'))
            ->visible(fn ($livewire) => ($record = static::recordOf($livewire))
                && (static::hasRevisionsFlow($livewire) && Revisions::supports($record) || ActivityTimeline::supports($record)))
            ->modalContent(function ($livewire) {
                $record = static::recordOf($livewire);

                return view('dashed-core::revisions.history-modal', [
                    'modelClass' => $record::class,
                    'recordKey' => (string) $record->getKey(),
                    'returnUrl' => static::returnUrl($livewire, $record),
                ]);
            });
    }

    protected static function recordOf($livewire): ?Model
    {
        return method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
    }

    /**
     * Revisies worden alleen vastgelegd door de opslag van HasEditableCMSActions.
     */
    protected static function hasRevisionsFlow($livewire): bool
    {
        return in_array(\Dashed\DashedCore\Filament\Concerns\HasEditableCMSActions::class, class_uses_recursive($livewire), true);
    }

    protected static function returnUrl($livewire, Model $record): string
    {
        return rescue(fn () => $livewire::getUrl(['record' => $record]), url()->previous(), false);
    }
}

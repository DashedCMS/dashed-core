<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Collection;
use Dashed\DashedCore\Models\ScheduledChange;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Dashed\DashedCore\Classes\Changes\ChangeSet;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Dashed\DashedCore\Filament\Resources\ScheduledChangeResource;

/**
 * "Wijziging inplannen" op een bewerkpagina (make()) en als bulkactie op een
 * lijst (bulk()). Welke velden er te kiezen zijn, bepaalt ChangeFields.
 */
class ScheduleChangeAction
{
    public static function make(): Action
    {
        return Action::make('scheduleChange')
            ->label(__('Wijziging inplannen'))
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->visible(fn (?Model $record) => $record && ChangeFields::supports($record, 'schedule') && (auth()->user()?->can('update', $record) ?? false))
            ->modalHeading(__('Wijziging inplannen'))
            ->modalDescription(fn (Model $record) => static::existingDescription($record))
            ->modalSubmitActionLabel(__('Inplannen'))
            ->schema(fn (Model $record) => static::schema($record::class, $record))
            ->action(function (Model $record, array $data, Action $action, $livewire) {
                $locale = property_exists($livewire, 'activeLocale') && $livewire->activeLocale ? $livewire->activeLocale : app()->getLocale();

                if (! static::store(collect([$record]), $data, $locale)) {
                    $action->halt();
                }
            });
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('scheduleChanges')
            ->label(__('Wijziging inplannen'))
            ->icon('heroicon-o-clock')
            ->authorizeIndividualRecords('update')
            ->modalHeading(__('Wijziging inplannen voor de selectie'))
            ->modalSubmitActionLabel(__('Inplannen'))
            ->schema(fn (BulkAction $action) => static::schema((string) $action->getModel()))
            ->action(function (Collection $records, array $data, BulkAction $action) {
                if (! static::store($records, $data, app()->getLocale())) {
                    $action->halt();
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(string $modelClass, ?Model $record = null): array
    {
        return [
            Section::make(__('Wat moet er veranderen?'))
                ->schema(ChangeSet::schema(ChangeFields::for($modelClass, 'schedule'), $modelClass, $record))
                ->compact(),
            Section::make(__('Wanneer?'))
                ->schema([
                    DateTimePicker::make('run_at')
                        ->label(__('Uitvoeren op'))
                        ->seconds(false)
                        ->required()
                        ->default(now()->addHour()->startOfHour()),
                    Toggle::make('revert')
                        ->label(__('Later automatisch terugdraaien'))
                        ->helperText(__('Bijvoorbeeld na Black Friday. Velden die iemand in de tussentijd zelf heeft aangepast, blijven staan.'))
                        ->live(),
                    DateTimePicker::make('revert_at')
                        ->label(__('Terugdraaien op'))
                        ->seconds(false)
                        ->visible(fn (Get $get) => (bool) $get('revert'))
                        ->required(fn (Get $get) => (bool) $get('revert'))
                        ->after('run_at'),
                    TextInput::make('label')
                        ->label(__('Omschrijving'))
                        ->placeholder(__('Bijvoorbeeld: Black Friday'))
                        ->maxLength(255),
                ])
                ->compact(),
        ];
    }

    /**
     * Maakt per record een geplande wijziging aan. Geeft false terug (met een
     * melding) als er niets te plannen viel.
     */
    public static function store(Collection|\Illuminate\Support\Collection $records, array $data, ?string $locale = null): bool
    {
        $first = $records->first();

        if (! $first) {
            return false;
        }

        $fields = ChangeFields::for($first, 'schedule');
        $changes = ChangeSet::fromForm($fields, $data);

        if (! $changes) {
            Notification::make()->title(__('Kies minstens één wijziging'))->danger()->send();

            return false;
        }

        $usesLocale = collect($changes)->keys()->contains(fn ($key) => $fields[$key]->isTranslatable());

        foreach ($records as $record) {
            ScheduledChange::query()->create([
                'model_type' => $record->getMorphClass(),
                'model_id' => (string) $record->getKey(),
                'label' => $data['label'] ?? null,
                'locale' => $usesLocale ? $locale : null,
                'changes' => $changes,
                'run_at' => $data['run_at'],
                'revert_at' => ! empty($data['revert']) ? ($data['revert_at'] ?? null) : null,
                'status' => ScheduledChange::STATUS_SCHEDULED,
                'user_id' => auth()->id(),
            ]);
        }

        Notification::make()
            ->title(trans_choice('Wijziging ingepland voor :count record|Wijziging ingepland voor :count records', $records->count()))
            ->body(ChangeSet::describe($fields, $changes))
            ->success()
            ->actions(rescue(fn () => [Action::make('overview')->label(__('Geplande wijzigingen'))->url(ScheduledChangeResource::getUrl())], [], false))
            ->send();

        return true;
    }

    protected static function existingDescription(Model $record): ?string
    {
        $count = ScheduledChange::query()
            ->where('model_type', $record->getMorphClass())
            ->where('model_id', (string) $record->getKey())
            ->whereIn('status', [ScheduledChange::STATUS_SCHEDULED, ScheduledChange::STATUS_APPLIED])
            ->where(fn ($query) => $query->where('status', ScheduledChange::STATUS_SCHEDULED)->orWhereNotNull('revert_at'))
            ->count();

        return $count
            ? trans_choice('Er staat al :count wijziging gepland of nog terug te draaien voor dit record.|Er staan al :count wijzigingen gepland of nog terug te draaien voor dit record.', $count)
            : null;
    }
}

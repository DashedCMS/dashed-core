<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Models\ScheduledChange;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Illuminate\Database\Eloquent\Relations\Relation;
use Dashed\DashedCore\Classes\Changes\ScheduledChangeRunner;
use Dashed\DashedCore\Filament\Resources\ScheduledChangeResource\Pages\ListScheduledChanges;

/**
 * Overzicht van ingeplande wijzigingen, met annuleren, nu uitvoeren, nu
 * terugdraaien en opnieuw proberen. Aanmaken gebeurt op het record zelf
 * (ScheduleChangeAction), niet hier.
 */
class ScheduledChangeResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = ScheduledChange::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Geplande wijzigingen';

    protected static ?string $label = 'Geplande wijziging';

    protected static ?string $pluralLabel = 'Geplande wijzigingen';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = rescue(fn () => ScheduledChange::query()
            ->whereIn('status', [ScheduledChange::STATUS_FAILED, ScheduledChange::STATUS_REVERT_FAILED])
            ->count(), 0, false);

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Mislukte geplande wijzigingen');
    }

    /**
     * Alleen rijen van modellen die er nog zijn: een project dat een pakket
     * verwijdert krijgt anders een fatale fout bij het eager loaden van het
     * record.
     */
    public static function getEloquentQuery(): Builder
    {
        $types = rescue(fn () => ScheduledChange::query()->distinct()->pluck('model_type')
            ->filter(fn (string $type) => class_exists(Relation::getMorphedModel($type) ?? $type))
            ->values()
            ->all(), [], false);

        return parent::getEloquentQuery()
            ->whereIn('model_type', $types)
            ->with(['model', 'user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('run_at', 'desc')
            ->columns([
                TextColumn::make('model_type')
                    ->label(__('Soort'))
                    ->formatStateUsing(fn (string $state) => ChangeFields::modelLabel($state)),
                TextColumn::make('model_id')
                    ->label(__('Record'))
                    ->formatStateUsing(fn (ScheduledChange $record) => ChangeFields::recordLabel($record->model, (string) $record->model_id))
                    ->url(fn (ScheduledChange $record) => ChangeFields::recordUrl($record->model)),
                TextColumn::make('label')
                    ->label(__('Omschrijving'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('changes')
                    ->label(__('Wijziging'))
                    ->state(fn (ScheduledChange $record) => $record->summary())
                    ->wrap(),
                TextColumn::make('run_at')
                    ->label(__('Uitvoeren op'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('revert_at')
                    ->label(__('Terugdraaien op'))
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ScheduledChange::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state) => ScheduledChange::statusColors()[$state] ?? 'gray')
                    ->description(fn (ScheduledChange $record) => $record->error
                        ?: ($record->skipped ? __('Niet teruggezet, sindsdien zelf gewijzigd: :velden', ['velden' => implode(', ', $record->skipped)]) : null)),
                TextColumn::make('user.name')
                    ->label(__('Ingepland door'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->multiple()
                    ->options(ScheduledChange::statusLabels()),
                SelectFilter::make('model_type')
                    ->label(__('Soort'))
                    ->options(fn () => ScheduledChange::query()->distinct()->pluck('model_type')
                        ->filter(fn (string $type) => class_exists(Relation::getMorphedModel($type) ?? $type))
                        ->mapWithKeys(fn (string $type) => [$type => ChangeFields::modelLabel($type)])
                        ->all()),
            ])
            ->recordActions([
                Action::make('runNow')
                    ->label(__('Nu uitvoeren'))
                    ->icon('heroicon-o-play')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledChange $record) => $record->status === ScheduledChange::STATUS_SCHEDULED && static::canManage())
                    ->action(function (ScheduledChange $record) {
                        $ok = ScheduledChangeRunner::apply($record);
                        static::report($ok, __('Wijziging uitgevoerd'), $record);
                    }),
                Action::make('revertNow')
                    ->label(__('Nu terugdraaien'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledChange $record) => $record->status === ScheduledChange::STATUS_APPLIED && static::canManage())
                    ->action(function (ScheduledChange $record) {
                        $record->forceFill(['revert_at' => now()])->save();
                        $ok = ScheduledChangeRunner::revert($record);
                        static::report($ok, __('Wijziging teruggedraaid'), $record);
                    }),
                Action::make('keep')
                    ->label(__('Niet terugdraaien'))
                    ->icon('heroicon-o-lock-closed')
                    ->requiresConfirmation()
                    ->modalDescription(__('De wijziging blijft staan en wordt niet meer automatisch teruggedraaid.'))
                    ->visible(fn (ScheduledChange $record) => $record->waitsForRevert() && static::canManage())
                    ->action(fn (ScheduledChange $record) => $record->forceFill(['revert_at' => null])->save()),
                Action::make('retry')
                    ->label(__('Opnieuw proberen'))
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (ScheduledChange $record) => in_array($record->status, [ScheduledChange::STATUS_FAILED, ScheduledChange::STATUS_REVERT_FAILED], true) && static::canManage())
                    ->action(function (ScheduledChange $record) {
                        $wasRevert = $record->status === ScheduledChange::STATUS_REVERT_FAILED;
                        ScheduledChangeRunner::retry($record);
                        $ok = $wasRevert ? ScheduledChangeRunner::revert($record) : ScheduledChangeRunner::apply($record);
                        static::report($ok, $wasRevert ? __('Wijziging teruggedraaid') : __('Wijziging uitgevoerd'), $record);
                    }),
                Action::make('cancel')
                    ->label(__('Annuleren'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduledChange $record) => $record->status === ScheduledChange::STATUS_SCHEDULED && static::canManage())
                    ->action(fn (ScheduledChange $record) => ScheduledChange::query()
                        ->whereKey($record->getKey())
                        ->where('status', ScheduledChange::STATUS_SCHEDULED)
                        ->update(['status' => ScheduledChange::STATUS_CANCELLED])),
                DeleteAction::make()
                    ->visible(fn (ScheduledChange $record) => ! $record->waitsForRevert()),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduledChanges::route('/'),
        ];
    }

    protected static function canManage(): bool
    {
        return auth()->user()?->can('edit_scheduled_change') ?? false;
    }

    protected static function report(bool $ok, string $title, ScheduledChange $record): void
    {
        $record->refresh();

        Notification::make()
            ->title($ok ? $title : __('Dat lukte niet'))
            ->body($ok ? null : $record->error)
            ->status($ok ? 'success' : 'danger')
            ->send();
    }
}

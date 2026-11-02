<?php

namespace Dashed\DashedCore\Filament\Resources\NotFoundPageResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * Alle hits van één niet gevonden pagina, met de verwijzer voorop: daaraan
 * zie je of de kapotte link op de eigen site staat of van buiten komt.
 */
class OccurrencesRelationManager extends RelationManager
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesRelationManagerLabels;

    protected static string $relationship = 'occurrences';

    protected static ?string $title = 'Hits';

    protected static ?string $modelLabel = 'hit';

    protected static ?string $pluralModelLabel = 'hits';

    public function table(Table $table): Table
    {
        $host = request()->getHost();

        return $table
            ->recordTitleAttribute('referer')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Wanneer'))
                    ->dateTime('d-m-Y H:i:s', 'Europe/Amsterdam')
                    ->sortable(),
                TextColumn::make('referer')
                    ->label(__('Verwijzer'))
                    ->placeholder(__('Geen verwijzer'))
                    ->url(fn ($record) => $record->referer ?: null)
                    ->openUrlInNewTab()
                    ->limit(70)
                    ->tooltip(fn ($record) => strlen((string) $record->referer) > 70 ? $record->referer : null)
                    ->badge(fn ($record) => (bool) $record->referer)
                    ->color(fn ($record) => str_contains((string) $record->referer, '://' . $host) ? 'warning' : 'gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(__('IP-adres'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_agent')
                    ->label(__('User agent'))
                    ->limit(50)
                    ->tooltip(fn ($record) => strlen((string) $record->user_agent) > 50 ? $record->user_agent : null)
                    ->searchable(),
                TextColumn::make('status_code')
                    ->label(__('Status'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('referer')
                    ->label(__('Verwijzer'))
                    ->getTitleFromRecordUsing(fn ($record) => $record->referer ?: __('Geen verwijzer'))
                    ->collapsible(),
                Group::make('ip_address')
                    ->label(__('IP-adres'))
                    ->collapsible(),
            ])
            ->filters([
                TernaryFilter::make('own_site_referer')
                    ->label(__('Verwijzer op eigen site'))
                    ->placeholder(__('Alle hits'))
                    ->trueLabel(__('Alleen vanaf eigen site'))
                    ->falseLabel(__('Alleen van buiten'))
                    ->queries(
                        true: fn (Builder $query) => $query->referredFromOwnSite($host),
                        false: fn (Builder $query) => $query->referredFromElsewhere($host),
                    ),
                TernaryFilter::make('has_referer')
                    ->label(__('Verwijzer bekend'))
                    ->placeholder(__('Alle hits'))
                    ->trueLabel(__('Met verwijzer'))
                    ->falseLabel(__('Zonder verwijzer'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('referer')->where('referer', '!=', ''),
                        false: fn (Builder $query) => $query->where(fn (Builder $q) => $q->whereNull('referer')->orWhere('referer', '')),
                    ),
            ])
            ->paginated([25, 50, 100])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}

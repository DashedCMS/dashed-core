<?php

namespace Dashed\DashedCore\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Dashed\DashedCore\Satisfaction\Satisfaction;
use Dashed\DashedCore\Models\SatisfactionRating;

/**
 * Lage scores (1 of 2) met de opmerking erbij, nieuwste eerst: de klanten
 * waar iemand naar moet kijken.
 */
class LowSatisfactionWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view_satisfaction');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Lage tevredenheidsscores'))
            ->query(fn () => SatisfactionRating::query()
                ->rated()
                ->where('score', '<=', Satisfaction::LOW_SCORE)
                ->latest('rated_at'))
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('Geen lage scores'))
            ->columns([
                TextColumn::make('rated_at')
                    ->label(__('Datum'))
                    ->dateTime('d-m-Y H:i'),
                TextColumn::make('source')
                    ->label(__('Bron'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => Satisfaction::sources()[$state] ?? $state),
                TextColumn::make('score')
                    ->label(__('Score'))
                    ->badge()
                    ->color('danger'),
                TextColumn::make('comment')
                    ->label(__('Opmerking'))
                    ->wrap()
                    ->limit(200)
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->label(__('Klant'))
                    ->placeholder('-'),
                TextColumn::make('staff_name')
                    ->label(__('Medewerker'))
                    ->placeholder('-'),
                TextColumn::make('order_id')
                    ->label(__('Bestelling'))
                    ->placeholder('-'),
            ]);
    }
}

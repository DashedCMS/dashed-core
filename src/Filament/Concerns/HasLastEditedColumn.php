<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Filament\Columns\LastEditedColumn;

/**
 * Filament resource trait that wires up the LastEditedColumn for any
 * resource whose model has both a `LogsActivity` trait AND a
 * `latestActivity` MorphOne relation.
 *
 * Usage on a resource:
 *
 *     class OrderResource extends Resource
 *     {
 *         use HasLastEditedColumn;
 *
 *         public static function table(Table $table): Table
 *         {
 *             return $table
 *                 ->columns([
 *                     // … existing columns …
 *                     static::lastEditedColumn(),
 *                 ])
 *                 ->modifyQueryUsing(fn (Builder $q) => static::modifyTableQueryForLastEdited($q));
 *         }
 *     }
 */
trait HasLastEditedColumn
{
    public static function lastEditedColumn(string $label = 'Laatst bewerkt'): LastEditedColumn
    {
        return LastEditedColumn::make('latest_activity')
            ->label($label)
            ->toggleable();
    }

    public static function modifyTableQueryForLastEdited(Builder $query): Builder
    {
        return $query->with(['latestActivity', 'latestActivity.causer']);
    }
}

<?php

namespace Dashed\DashedCore\Filament\Resources\Reviews\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Dashed\DashedCore\Models\Review;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),

                TextColumn::make('stars')
                    ->label('⭐')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('review')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->multiple()
                    ->options(function () {
                        $providers = [];
                        foreach (Review::distinct('provider')->pluck('provider') as $provider) {
                            $providers[$provider] = ucfirst($provider);
                        }

                        return $providers;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

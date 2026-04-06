<?php

namespace Dashed\DashedCore\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Dashed\DashedCore\Models\ArticleDraft;
use Dashed\DashedCore\Filament\Resources\ArticleDraftResource\Pages;

class ArticleDraftResource extends Resource
{
    protected static ?string $model = ArticleDraft::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';
    protected static string|\UnitEnum|null $navigationGroup = 'AI';
    protected static ?string $navigationLabel = 'Artikelen schrijven';
    protected static ?string $modelLabel = 'Artikel concept';
    protected static ?string $pluralModelLabel = 'Artikel concepten';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('keyword')
                    ->label('Zoekwoord')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('locale')
                    ->label('Taal')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color),
                Tables\Columns\TextColumn::make('article_content.h1')
                    ->label('Artikel titel')
                    ->placeholder('(nog niet gegenereerd)')
                    ->limit(60),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('applied_at')
                    ->label('Toegepast op')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticleDrafts::route('/'),
            'create' => Pages\CreateArticleDraft::route('/create'),
            'view' => Pages\ViewArticleDraft::route('/{record}'),
        ];
    }
}

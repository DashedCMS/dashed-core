<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Models\SeoVerbetervoorstel;
use Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource\Pages\ListSeoVerbetervoorstellen;
use Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource\Pages\ViewSeoVerbetervoorstel;

class SeoVerbetervoorstelResource extends Resource
{
    protected static ?string $model = SeoVerbetervoorstel::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | UnitEnum | null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'SEO Verbetervoorstellen';

    protected static ?string $label = 'SEO Verbetervoorstel';

    protected static ?string $pluralLabel = 'SEO Verbetervoorstellen';

    protected static ?int $navigationSort = 10;

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('applied_at')
                    ->label('Toegepast op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()->button(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoVerbetervoorstellen::route('/'),
            'view' => ViewSeoVerbetervoorstel::route('/{record}'),
        ];
    }
}

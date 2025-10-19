<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages\EditGlobalBlock;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages\ListGlobalBlocks;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages\CreateGlobalBlock;

class GlobalBlockResource extends Resource
{
    use Translatable;

    protected static ?string $model = GlobalBlock::class;

    protected static ?string $recordTitleAttribute = 'name';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string | UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Globale blokken';
    protected static ?string $label = 'Globaal blok';
    protected static ?string $pluralLabel = 'Globale blokken';
    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Content')
                    ->schema(array_merge([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->lazy()
                        ->columnSpanFull(),
                        cms()->getFilamentBuilderBlock(globalBlockChooser: false),
                    ]))
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_merge([
                TextColumn::make('name')
                    ->label('Naam')
                    ->sortable()
                    ->searchable(),
            ]))
            ->filters([
                TrashedFilter::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->button(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGlobalBlocks::route('/'),
            'create' => CreateGlobalBlock::route('/create'),
            'edit' => EditGlobalBlock::route('/{record}/edit'),
        ];
    }
}

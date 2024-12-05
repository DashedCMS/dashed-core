<?php

namespace Dashed\DashedCore\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages\EditGlobalBlock;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages\ListGlobalBlocks;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages\CreateGlobalBlock;

class GlobalBlockResource extends Resource
{
    use Translatable;

    protected static ?string $model = GlobalBlock::class;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Content';
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

    public static function form(Form $form): Form
    {
        return $form
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
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->actions([
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

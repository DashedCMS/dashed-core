<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Dashed\DashedCore\Models\Redirect;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Filament\Resources\RedirectResource\Pages\EditRedirect;
use Dashed\DashedCore\Filament\Resources\RedirectResource\Pages\ListRedirects;
use Dashed\DashedCore\Filament\Resources\RedirectResource\Pages\CreateRedirect;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $recordTitleAttribute = 'from';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-link';

    protected static string | UnitEnum | null $navigationGroup = 'Systeem';

    protected static ?string $navigationLabel = 'Redirects';

    protected static ?string $label = 'Redirect';

    protected static ?string $pluralLabel = 'Redirects';
    protected static ?int $navigationSort = 5;

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(
                [
                    Section::make(__('content'))->columnSpanFull()
                        ->schema(
                            array_merge([
                                Forms\Components\TextInput::make('from')
                                    ->required()
                                    ->label(__('Vanaf welke URL moet er een redirect komen?'))
                                    ->helperText(__('Bijv: /dit-is-een-oude-url')),
                                Forms\Components\TextInput::make('to')
                                    ->required()
                                    ->label(__('Naar welke URL moet deze redirect verwijzen?'))
                                    ->helperText(__('Bijv: /dit-is-een-nieuwe-url of https://dashed.com/wij-programmeren-kei-goed')),
                                Forms\Components\Select::make('sort')
                                    ->required()
                                    ->label(__('Type redirect'))
                                    ->default('301')
                                    ->options([
                                        '301' => __('Permanente redirect'),
                                        '302' => __('Tijdelijke redirect'),
                                    ]),
                                Forms\Components\DatePicker::make('delete_redirect_after')
                                    ->label(__('Verwijder redirect na een datum'))
                                    ->default(now()->addMonths(3)),
                            ])
                        ),]
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),
                TextColumn::make('from')
                    ->url(fn ($record) => url($record->from))
                    ->openUrlInNewTab()
                    ->label(__('Oude URL'))
                    ->searchable(),
                TextColumn::make('to')
                    ->url(fn ($record) => $record->to)
                    ->openUrlInNewTab()
                    ->label(__('Nieuwe URL'))
                    ->searchable(),
                TextColumn::make('sort')
                    ->label(__('Soort redirect')),
                TextColumn::make('delete_redirect_after')
                    ->label(__('Delete redirect na'))
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->delete_redirect_after ? $record->delete_redirect_after->format('d-m-Y') : 'Niet verwijderen'),
            ])
            ->recordActions([
                EditAction::make()
                    ->button(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }
}

<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\CreatePage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\EditPage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\ListPages;
use Qubiqx\QcommerceCore\Models\Page;

class PageResource extends Resource
{
    use Translatable;

    protected static ?string $model = Page::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Pagina\'s';
    protected static ?string $label = 'Pagina';
    protected static ?string $pluralLabel = 'Pagina\'s';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Globale informatie')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Vul een startdatum in voor de pagina:')
                            ->helperText('Indien je geen startdatum opgeeft, is de pagina direct zichtbaar'),
                        DatePicker::make('end_date')
                            ->label('Vul een einddatum in voor de pagina:')
                            ->helperText('Indien je geen einddatum opgeeft, vervalt de pagina niet'),
                        Toggle::make('is_home')
                            ->label('Dit is de homepagina'),
                        Select::make('site_id')
                            ->label('Actief op site')
                            ->options(collect(Sites::getSites())->pluck('name', 'id'))
                            ->hidden(function () {
                                return !(Sites::getAmountOfSites() > 1);
                            })
                            ->required()
                    ]),
                Section::make('Content')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                        ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}

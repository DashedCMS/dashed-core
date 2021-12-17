<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\CreatePage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\EditPage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\ListPages;
use Qubiqx\QcommerceCore\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Pagina\'s';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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

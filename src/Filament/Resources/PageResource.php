<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\CreatePage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\EditPage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\ListPages;
use Qubiqx\QcommerceCore\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

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

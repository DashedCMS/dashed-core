<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\CreatePage;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages\EditPage;
use Qubiqx\QcommerceCore\Filament\Resources\TranslationResource\Pages\EditTranslation;
use Qubiqx\QcommerceCore\Filament\Resources\TranslationResource\Pages\ListTranslations;
use Qubiqx\QcommerceCore\Models\Translation;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-translate';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Vertalingen';
    protected static ?string $label = 'Vertaling';
    protected static ?string $pluralLabel = 'Vertalingen';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'tag',
            'name',
            'default',
            'value',
            'type',
        ];
    }

    public static function form(Form $form): Form
    {
        return [];
    }

    public static function table(Table $table): Table
    {
        return [];
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
            'index' => ListTranslations::route('/'),
            'edit' => EditTranslation::route('/{record}/edit'),
        ];
    }
}

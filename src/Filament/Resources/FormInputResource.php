<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Qubiqx\QcommerceCore\Filament\Resources\FormInputResource\Pages\ViewFormInput;
use Qubiqx\QcommerceCore\Models\FormInput;

class FormInputResource extends Resource
{
    protected static ?string $model = FormInput::class;
    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $label = 'Formulier invoer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Menu')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewFormInput::route('/{record}'),
        ];
    }
}

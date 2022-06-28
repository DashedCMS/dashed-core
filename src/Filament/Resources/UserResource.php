<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use App\Models\User;
use Closure;
use Filament\Forms\Components\BelongsToSelect;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource\Users\EditUser;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource\Users\ListUsers;
use Qubiqx\QcommercePages\Filament\Resources\PageResource\Pages\CreatePage;
use Qubiqx\QcommercePages\Filament\Resources\PageResource\Pages\EditPage;
use Qubiqx\QcommercePages\Filament\Resources\PageResource\Pages\ListPages;
use Qubiqx\QcommercePages\Models\Page;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gebruikers';
    protected static ?string $navigationLabel = 'Gebruikers';
    protected static ?string $label = 'Gebruiker';
    protected static ?string $pluralLabel = 'Gebruikers';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'email',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Naam')
                    ->required()
                    ->rules([
                        'required',
                        'max:255',
                    ]),
                TextInput::make('email')
                    ->label('Email')
                    ->unique('users', 'email', fn($record) => $record)
                    ->required()
                    ->rules([
                        'required',
                        'email:rfc',
                        'max:255',
                    ]),
                TextInput::make('password')
                    ->label('Wachtwoord')
                    ->nullable()
                    ->password()
                    ->rules([
                        'nullable',
                        'min:6',
                        'max:255',
                        'confirmed',
                    ])
                    ->helperText('Het wachtwoord wordt alleen aangepast als je iets invult')
                    ->reactive(),
                TextInput::make('password_confirmation')
                    ->label('Wachtwoord herhalen')
                    ->required(fn(Closure $get) => $get('password'))
                    ->password()
                    ->rules([
                        'min:6',
                        'max:255',
                    ])
                    ->reactive(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
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
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

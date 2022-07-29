<?php

namespace Qubiqx\QcommerceCore\Filament\Resources;

use Closure;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource\Users\CreateUser;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource\Users\EditUser;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource\Users\ListUsers;
use Qubiqx\QcommerceEcommerceCore\Filament\Resources\ShippingZoneResource\Pages\EditShippingZone;

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
                    ->unique('users', 'email', fn ($record) => $record)
                    ->required()
                    ->rules([
                        'required',
                        'email:rfc',
                        'max:255',
                    ]),
                Select::make('role')
                    ->label('Rol')
                    ->required()
                    ->options([
                        'customer' => 'Customer',
                        'admin' => 'Admin',
                    ])
                    ->rules([
                        'required',
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
                    ->required(fn ($livewire) => $livewire instanceof CreateUser)
                    ->helperText('Het wachtwoord wordt alleen aangepast als je iets invult')
                    ->reactive(),
                TextInput::make('password_confirmation')
                    ->label('Wachtwoord herhalen')
                    ->required(fn (Closure $get) => $get('password'))
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
                TextColumn::make('role')
                    ->label('Rol'),
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
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

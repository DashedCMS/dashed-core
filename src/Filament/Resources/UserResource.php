<?php

namespace Dashed\DashedCore\Filament\Resources;

use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\EditUser;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\ListUsers;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\CreateUser;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Gebruikers';

    protected static ?string $navigationLabel = 'Gebruikers';

    protected static ?string $label = 'Gebruiker';

    protected static ?string $pluralLabel = 'Gebruikers';

    public static function shouldRegisterNavigation(): bool
    {
        return config('dashed-core.show_default_user_resource', true);
    }

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
                Section::make('Gebruiker')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Voornaam')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Achternaam')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->unique('users', 'email', fn ($record) => $record)
                            ->required()
                            ->email()
                            ->maxLength(255),
                        Select::make('role')
                            ->label('Rol')
                            ->required()
                            ->options([
                                'customer' => 'Customer',
                                'admin' => 'Admin',
                            ]),
                        TextInput::make('password')
                            ->label('Wachtwoord')
                            ->nullable()
                            ->password()
                            ->confirmed()
                            ->minLength(6)
                            ->maxLength(255)
                            ->required(fn ($livewire) => $livewire instanceof CreateUser)
                            ->helperText('Het wachtwoord wordt alleen aangepast als je iets invult')
                            ->reactive(),
                        TextInput::make('password_confirmation')
                            ->label('Wachtwoord herhalen')
                            ->required(fn (Get $get) => $get('password'))
                            ->password()
                            ->minLength(6)
                            ->maxLength(255)
                            ->reactive(),
                    ])
                    ->columns(2),
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
            ])
            ->actions([
                EditAction::make()
                    ->button(),
                DeleteAction::make(),
            ])
            ->bulkActions([
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

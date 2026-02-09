<?php

namespace Dashed\DashedCore\Filament\Resources;

use STS\FilamentImpersonate\Actions\Impersonate;
use UnitEnum;
use BackedEnum;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\EditUser;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\ListUsers;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\CreateUser;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | UnitEnum | null $navigationGroup = 'Gebruikers';

    protected static ?string $navigationLabel = 'Gebruikers';

    protected static ?string $label = 'Gebruiker';

    protected static ?string $pluralLabel = 'Gebruikers';
    protected static ?int $navigationSort = 100;

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Gebruiker')->columnSpanFull()
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
            ->recordActions([
                Impersonate::make(),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

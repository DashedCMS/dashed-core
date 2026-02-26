<?php

namespace Dashed\DashedCore\Filament\Resources;

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
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use STS\FilamentImpersonate\Actions\Impersonate;
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

                Section::make('Contact')->columnSpanFull()
                    ->schema([
                        TextInput::make('phone_number')
                            ->label('Telefoonnummer')
                            ->tel()
                            ->maxLength(32),

                        TextInput::make('date_of_birth')
                            ->label('Geboortedatum')
                            ->type('date'),

                        Select::make('gender')
                            ->label('Geslacht')
                            ->options([
                                'm' => 'Man',
                                'f' => 'Vrouw',
                            ])
                            ->nullable(),

                        Select::make('marketing')
                            ->label('Nieuwsbrief')
                            ->options([
                                0 => 'Nee',
                                1 => 'Ja',
                            ])
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Verzendadres')->columnSpanFull()
                    ->schema([
                        TextInput::make('street')
                            ->label('Straat')
                            ->maxLength(255),

                        TextInput::make('house_nr')
                            ->label('Huisnummer')
                            ->maxLength(50),

                        TextInput::make('zip_code')
                            ->label('Postcode')
                            ->maxLength(50),

                        TextInput::make('city')
                            ->label('Stad')
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label('Land')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Bedrijf')->columnSpanFull()
                    ->schema([
                        Toggle::make('is_company')
                            ->label('Bestelt als bedrijf')
                            ->default(0)
                            ->columnSpanFull()
                            ->reactive(),

                        TextInput::make('company')
                            ->label('Bedrijfsnaam')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => (bool) $get('is_company')),

                        TextInput::make('tax_id')
                            ->label('BTW ID')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => (bool) $get('is_company')),
                    ])
                    ->columns(2),

                Section::make('Factuuradres')->columnSpanFull()
                    ->schema([
                        TextInput::make('invoice_street')
                            ->label('Straat')
                            ->maxLength(255),

                        TextInput::make('invoice_house_nr')
                            ->label('Huisnummer')
                            ->maxLength(50),

                        TextInput::make('invoice_zip_code')
                            ->label('Postcode')
                            ->maxLength(50),

                        TextInput::make('invoice_city')
                            ->label('Stad')
                            ->maxLength(255),

                        TextInput::make('invoice_country')
                            ->label('Land')
                            ->maxLength(255),
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

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

    /**
     * Mag deze rol een back-office account aanmaken?
     *
     * Zo'n account krijgt de superadmin-rol, dus alleen een superadmin mag dit.
     * Zou een gewone admin het ook mogen, dan kon die zichzelf via een tweede
     * account opwaarderen.
     */
    public static function canAssignBackOfficeRole(?string $currentRole): bool
    {
        return $currentRole === 'superadmin';
    }

    /**
     * Rollen die de ingelogde gebruiker mag toekennen.
     *
     * "Admin" slaat superadmin op: dat is de rol die via Gate::before overal
     * toegang krijgt. Een gebruiker met de oude 'admin'-rol haalt zijn rechten
     * uit gekoppelde roles en kan zonder die koppeling niets, dus die rol wordt
     * niet meer uitgedeeld.
     *
     * Alleen een superadmin mag de rol toekennen. Zou een gewone admin dat ook
     * mogen, dan kon die zichzelf via een tweede account opwaarderen.
     *
     * @return array<string, string>
     */
    public static function roleOptions(?string $currentRole, ?string $recordRole = null): array
    {
        $options = ['customer' => 'Customer'];

        if (static::canAssignBackOfficeRole($currentRole)) {
            $options['superadmin'] = 'Admin';
        }

        // Bestaande gebruikers met de oude rol moeten bewerkbaar blijven. Zonder
        // deze optie staat er een waarde in het verplichte veld die niet in de
        // lijst voorkomt, en dan faalt het opslaan op de validatie.
        if ($recordRole === 'admin') {
            $options['admin'] = 'Admin (oude rol, rechten via rollen)';
        }

        return $options;
    }

    /**
     * Of het rollen-veld zichtbaar is bij deze combinatie.
     *
     * Het veld hing aan role === 'admin', en die rol wordt sinds de
     * security-hardening niet meer uitgedeeld. Daarmee was er geen enkele weg
     * meer om een nieuwe beheerder beperkte rechten te geven: de enige
     * kiesbare back-office rol is superadmin, en die komt via Gate::before
     * overal doorheen. Een gebruiker met gekoppelde rollen mag het paneel in
     * (zie User::canAccessPanel) en krijgt precies de extra_permissions van die
     * rollen, dus dit is de plek waar een beperkte beheerder gemaakt wordt.
     *
     * Bij superadmin blijft het veld weg: die heeft alles al, en een lijst die
     * niets doet leest als een lijst die wel iets doet.
     *
     * Rollen uitdelen is rechten uitdelen, dus het staat achter dezelfde deur
     * als de back-office rol zelf. Zonder die grendel kan een beheerder met
     * gebruikersrechten zichzelf elke bevoegdheid toekennen.
     */
    public static function canSeeRolesField(?string $currentRole, ?string $selectedRole): bool
    {
        return static::canAssignBackOfficeRole($currentRole) && $selectedRole !== 'superadmin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('Gebruiker'))->columnSpanFull()
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('Voornaam'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->label(__('Achternaam'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('Email'))
                            ->unique('users', 'email', fn ($record) => $record)
                            ->required()
                            ->email()
                            ->maxLength(255),

                        Select::make('role')
                            ->label(__('Rol'))
                            ->required()
                            ->reactive()
                            // De options-closure wordt server-side hergebruikt voor validatie,
                            // dus een gespoofde waarde wordt geweigerd.
                            ->options(fn ($record) => static::roleOptions(auth()->user()?->role, $record?->role)),

                        Select::make('roles')
                            ->label(__('Rollen'))
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->visible(fn (Get $get) => static::canSeeRolesField(auth()->user()?->role, $get('role')))
                            ->helperText(__('Wijs rollen toe om te bepalen tot welke onderdelen deze gebruiker toegang heeft. Een gebruiker met rollen mag de beheeromgeving in, ook zonder beheerdersrol.')),

                        TextInput::make('password')
                            ->label(__('Wachtwoord'))
                            ->nullable()
                            ->password()
                            ->confirmed()
                            ->minLength(6)
                            ->maxLength(255)
                            ->required(fn ($livewire) => $livewire instanceof CreateUser)
                            ->helperText(__('Het wachtwoord wordt alleen aangepast als je iets invult'))
                            ->reactive(),

                        TextInput::make('password_confirmation')
                            ->label(__('Wachtwoord herhalen'))
                            ->required(fn (Get $get) => $get('password'))
                            ->password()
                            ->minLength(6)
                            ->maxLength(255)
                            ->reactive(),
                    ])
                    ->columns(2),

                Section::make(__('Contact'))->columnSpanFull()
                    ->schema([
                        TextInput::make('phone_number')
                            ->label(__('Telefoonnummer'))
                            ->tel()
                            ->maxLength(32),

                        TextInput::make('date_of_birth')
                            ->label(__('Geboortedatum'))
                            ->type('date'),

                        Select::make('gender')
                            ->label(__('Geslacht'))
                            ->options([
                                'm' => __('Man'),
                                'f' => __('Vrouw'),
                            ])
                            ->nullable(),

                        Select::make('marketing')
                            ->label(__('Nieuwsbrief'))
                            ->options([
                                0 => __('Nee'),
                                1 => __('Ja'),
                            ])
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make(__('Verzendadres'))->columnSpanFull()
                    ->schema([
                        TextInput::make('street')
                            ->label(__('Straat'))
                            ->maxLength(255),

                        TextInput::make('house_nr')
                            ->label(__('Huisnummer'))
                            ->maxLength(50),

                        TextInput::make('zip_code')
                            ->label(__('Postcode'))
                            ->maxLength(50),

                        TextInput::make('city')
                            ->label(__('Stad'))
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label(__('Land'))
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('Bedrijf'))->columnSpanFull()
                    ->schema([
                        Toggle::make('is_company')
                            ->label(__('Bestelt als bedrijf'))
                            ->default(0)
                            ->columnSpanFull()
                            ->reactive(),

                        TextInput::make('company')
                            ->label(__('Bedrijfsnaam'))
                            ->maxLength(255)
                            ->visible(fn (Get $get) => (bool) $get('is_company')),

                        TextInput::make('tax_id')
                            ->label(__('BTW ID'))
                            ->maxLength(255)
                            ->visible(fn (Get $get) => (bool) $get('is_company')),
                    ])
                    ->columns(2),

                Section::make(__('Factuuradres'))->columnSpanFull()
                    ->schema([
                        TextInput::make('invoice_street')
                            ->label(__('Straat'))
                            ->maxLength(255),

                        TextInput::make('invoice_house_nr')
                            ->label(__('Huisnummer'))
                            ->maxLength(50),

                        TextInput::make('invoice_zip_code')
                            ->label(__('Postcode'))
                            ->maxLength(50),

                        TextInput::make('invoice_city')
                            ->label(__('Stad'))
                            ->maxLength(255),

                        TextInput::make('invoice_country')
                            ->label(__('Land'))
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
                    ->label(__('Naam'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('Rol'))
                    ->sortable(),
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

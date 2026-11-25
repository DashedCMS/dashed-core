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
use Filament\Forms\Components\FileUpload;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Dashed\DashedCore\Classes\AdminLocale;
use Filament\Schemas\Components\Utilities\Get;
use STS\FilamentImpersonate\Actions\Impersonate;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\EditUser;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\ListUsers;
use Dashed\DashedCore\Filament\Resources\UserResource\Users\CreateUser;

class UserResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

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
     * Mag $actor toegang uitdelen: de vlag superadmin zetten, rollen koppelen
     * of via "Beheerder aanmaken" een back-office account maken?
     *
     * Alleen een superadmin. Zou een beheerder met gebruikersrechten dat ook
     * mogen, dan kon die zichzelf (of een tweede account) opwaarderen.
     */
    public static function canManageAccess(?\Dashed\DashedCore\Models\User $actor): bool
    {
        return (bool) $actor?->isSuperadmin();
    }

    /**
     * Mag $actor het e-mailadres en wachtwoord van $target wijzigen?
     *
     * Een account met toegang tot de beheeromgeving (superadmin of gekoppelde
     * rollen) is een overname waard: wie zijn e-mail of wachtwoord kan zetten,
     * logt als hem in. Dus alleen een superadmin, of de gebruiker zelf. Een
     * gewone klant blijft voor edit_user te beheren.
     */
    public static function canChangeCredentials(?\Dashed\DashedCore\Models\User $actor, ?\Dashed\DashedCore\Models\User $target): bool
    {
        if (! $actor) {
            return false;
        }

        if (! $target || ! $target->exists) {
            return true;
        }

        if ($actor->isSuperadmin() || $actor->is($target)) {
            return true;
        }

        return ! $target->mustLoginViaPanel();
    }

    /**
     * Of het rollen-veld zichtbaar is.
     *
     * Een gebruiker met gekoppelde rollen mag het paneel in (zie
     * User::hasPanelAccess) en krijgt precies de extra_permissions van die
     * rollen, dus dit is de plek waar een beperkte beheerder gemaakt wordt.
     *
     * Bij een superadmin blijft het veld weg: die heeft alles al, en een lijst
     * die niets doet leest als een lijst die wel iets doet.
     *
     * Rollen uitdelen is rechten uitdelen, dus het staat achter dezelfde deur
     * als de vlag superadmin zelf.
     */
    public static function canSeeRolesField(?\Dashed\DashedCore\Models\User $actor, bool $recordIsSuperadmin): bool
    {
        return static::canManageAccess($actor) && ! $recordIsSuperadmin;
    }

    /**
     * Waarom $actor de vlag superadmin van $target niet naar $wanted mag
     * zetten, of null als het mag. $target null is een nieuw account.
     *
     * Dit is de servercontrole achter de schakelaar; verbergen en uitschakelen
     * in het formulier is alleen de weergave.
     */
    public static function superadminChangeError(?\Dashed\DashedCore\Models\User $actor, ?\Dashed\DashedCore\Models\User $target, bool $wanted): ?string
    {
        $current = (bool) $target?->isSuperadmin();

        if ($wanted === $current) {
            return null;
        }

        if (! static::canManageAccess($actor)) {
            return __('Alleen een superadmin kan iemand superadmin maken of dat weer uitzetten.');
        }

        if ($wanted) {
            return null;
        }

        if ($target && $actor->is($target)) {
            return __('Je kunt superadmin niet bij jezelf uitzetten.');
        }

        if ($target && \Dashed\DashedCore\Models\User::query()->superadmins()->whereKeyNot($target->getKey())->doesntExist()) {
            return __('Dit is de laatste superadmin. Maak eerst iemand anders superadmin.');
        }

        return null;
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
                            ->disabled(fn ($record) => ! static::canChangeCredentials(auth()->user(), $record))
                            ->helperText(fn ($record) => static::canChangeCredentials(auth()->user(), $record) ? null : __('Alleen een superadmin of de gebruiker zelf kan het e-mailadres en wachtwoord van een beheerder wijzigen.'))
                            ->required()
                            ->email()
                            ->maxLength(255),

                        FileUpload::make('avatar_path')
                            ->label(__('Profielfoto'))
                            ->disk('dashed')
                            ->directory('avatars')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->maxSize(4096)
                            ->helperText(__('Zonder foto tonen we een cirkel met de initialen.')),

                                                Toggle::make('is_superadmin')
                            ->label(__('Superadmin'))
                            ->helperText(__('Een superadmin mag altijd alles, ook zonder rollen. Alleen een superadmin kan dit aan- of uitzetten.'))
                            ->live()
                            ->visible(fn () => static::canManageAccess(auth()->user()))
                            ->dehydrated(fn () => static::canManageAccess(auth()->user()))
                            // Bij jezelf staat hij vast: wie zichzelf uitzet sluit zich buiten.
                            ->disabled(fn ($record) => $record && auth()->user() && $record->is(auth()->user()))
                            ->rule(fn ($record) => function (string $attribute, $value, \Closure $fail) use ($record): void {
                                if ($error = static::superadminChangeError(auth()->user(), $record, (bool) $value)) {
                                    $fail($error);
                                }
                            }),

                        Select::make('admin_locale')
                            ->label(__('Taal van het CMS'))
                            ->options(AdminLocale::options())
                            ->in(array_keys(AdminLocale::options()))
                            ->placeholder(AdminLocale::options()[AdminLocale::default()] ?? null)
                            ->visible(fn () => (bool) auth()->user()?->isSuperadmin()),

                        Select::make('roles')
                            ->label(__('Rollen'))
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->visible(fn (Get $get) => static::canSeeRolesField(auth()->user(), (bool) $get('is_superadmin')))
                            ->helperText(__('Wijs rollen toe om te bepalen tot welke onderdelen deze gebruiker toegang heeft. Zonder rol en zonder superadmin is iemand klant en komt hij niet in de beheeromgeving.')),

                        TextInput::make('password')
                            ->label(__('Wachtwoord'))
                            ->visible(fn ($record) => static::canChangeCredentials(auth()->user(), $record))
                            ->nullable()
                            ->password()
                            ->confirmed()
                            ->rule(\Illuminate\Validation\Rules\Password::defaults())
                            ->maxLength(255)
                            ->required(fn ($livewire) => $livewire instanceof CreateUser)
                            ->helperText(__('Het wachtwoord wordt alleen aangepast als je iets invult'))
                            ->reactive(),

                        TextInput::make('password_confirmation')
                            ->label(__('Wachtwoord herhalen'))
                            ->visible(fn ($record) => static::canChangeCredentials(auth()->user(), $record))
                            ->required(fn (Get $get) => $get('password'))
                            ->password()
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
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('Naam'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('access')
                    ->label(__('Toegang'))
                    ->state(fn ($record) => $record->accessLabel())
                    ->badge()
                    ->color(fn ($record) => $record->isSuperadmin() ? 'danger' : ($record->roles->isNotEmpty() ? 'primary' : 'gray'))
                    // Superadmin eerst, dan beheerders, dan klanten.
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('is_superadmin', $direction === 'asc' ? 'desc' : 'asc')
                        ->orderBy(
                            \Dashed\DashedCore\Models\Role::query()
                                ->selectRaw('count(*)')
                                ->join('dashed__model_has_roles', 'dashed__model_has_roles.role_id', '=', 'dashed__roles.id')
                                ->whereColumn('dashed__model_has_roles.user_id', 'users.id'),
                            $direction === 'asc' ? 'desc' : 'asc'
                        )),
                TextColumn::make('created_at')
                    ->label(__('Aangemaakt op'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('access')
                    ->label(__('Toegang'))
                    ->multiple()
                    ->options([
                        'superadmin' => __('Superadmin'),
                        'beheerder' => __('Beheerder (met rollen)'),
                        'klant' => __('Klant'),
                    ])
                    ->query(function ($query, array $data) {
                        $values = array_values(array_filter((array) ($data['values'] ?? [])));

                        if ($values === []) {
                            return $query;
                        }

                        return $query->where(function ($query) use ($values) {
                            foreach ($values as $value) {
                                $query->orWhere(fn ($q) => match ($value) {
                                    'superadmin' => $q->superadmins(),
                                    'beheerder' => $q->where('is_superadmin', false)->whereHas('roles'),
                                    'klant' => $q->customers(),
                                    default => $q->whereRaw('1 = 0'),
                                });
                            }
                        });
                    }),
                SelectFilter::make('roles')
                    ->label(__('Rollen'))
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ])
            ->recordActions([
                Impersonate::make(),
                EditAction::make()
                    ->button(),
                \Dashed\DashedCore\Filament\Resources\UserResource\Actions\ResetMfaAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->mayBeManagedBy(auth()->user())),
                ...static::extraRecordActions(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Per record getoetst: beheerders en jezelf worden overgeslagen.
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn ($record) => $record->mayBeManagedBy(auth()->user())),
                ]),
            ]);
    }

    /**
     * Acties die andere pakketten aan een gebruiker hangen, zoals het
     * klantprofiel uit ec-core: cms()->builder('userResourceRecordActions',
     * ['sleutel' => fn () => Action::make(...)]). Een closure, zodat de
     * actie pas bij het renderen gebouwd wordt en niet bij de boot.
     *
     * @return array<int, \Filament\Actions\Action>
     */
    protected static function extraRecordActions(): array
    {
        return collect(cms()->builder('userResourceRecordActions'))
            ->map(fn ($action) => is_callable($action) ? $action() : $action)
            ->filter(fn ($action) => $action instanceof \Filament\Actions\Action)
            ->values()
            ->all();
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

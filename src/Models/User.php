<?php

namespace Dashed\DashedCore\Models;

use Filament\Panel;
use Dashed\DashedCore\Classes\SchemaColumns;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Classes\MfaFreshness;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Dashed\DashedCore\Traits\HasDynamicRelation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    use HasApiTokens;
    use HasDynamicRelation;
    use HasFactory;
    use LogsActivity;
    use Notifiable;

    protected static $factory = \Dashed\DashedCore\Database\Factories\UserFactory::class;

    protected static $logFillable = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Altijd versleuteld en verborgen, ook als een kindmodel $casts, casts() of $hidden overschrijft. */
    public const MFA_SECRET_COLUMNS = [
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'name',
    ];

    /**
     * App\Models\User in de klantprojecten overschrijft $casts of casts() met
     * de skeletonwaarden. Stonden de MFA-casts alleen daar, dan schreef Filament
     * het geheim onversleuteld weg en klapte elke lezing via dit basismodel (de
     * mobiele API) op DecryptException. Alle cast-logica van Eloquent loopt via
     * getCasts(), dus hier kan een kindmodel ze niet kwijtraken.
     */
    public function getCasts()
    {
        return [
            ...parent::getCasts(),
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'navigation_sort_by_usage' => 'boolean',
            'is_superadmin' => 'boolean',
        ];
    }

    /** Om dezelfde reden als getCasts(): een overschreven $hidden mag de MFA-velden niet prijsgeven. */
    public function getHidden()
    {
        return array_values(array_unique([...parent::getHidden(), ...self::MFA_SECRET_COLUMNS]));
    }

    /**
     * admin_locale en navigation_sort_by_usage moeten ook opgeslagen worden in klantprojecten, waar
     * App\Models\User $fillable overschrijft met de skeletonlijst. Daarom
     * hier en niet in $fillable, zoals de MFA-casts in getCasts().
     */
    public function getFillable()
    {
        return array_values(array_unique([...parent::getFillable(), 'admin_locale', 'navigation_sort_by_usage']));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected static function booted(): void
    {
        // Wie midden in een sessie (verplicht) MFA instelt, heeft die code net
        // zelf bevestigd. Stempel de sessie dan meteen als vers, anders stuurt
        // EnsureMfaIsFresh hem direct daarna nog een keer langs een codescherm.
        // In booted() en niet in de ServiceProvider, zodat het ook geldt voor
        // App\Models\User in de projecten, die hiervan erft.
        static::updated(function (self $user): void {
            if (! auth()->hasUser() || auth()->id() !== $user->getKey()) {
                return;
            }

            $justSetUp = ($user->wasChanged('app_authentication_secret') && $user->app_authentication_secret)
                || ($user->wasChanged('has_email_authentication') && $user->has_email_authentication);

            if ($justSetUp) {
                MfaFreshness::stamp();
            }
        });
    }

    /**
     * Toegang tot het CMS komt alleen nog uit twee bronnen: de vlag
     * is_superadmin (mag altijd alles) en gekoppelde rollen (rechten via
     * extra_permissions). Zonder een van beide ben je klant. De oude kolom
     * users.role bestaat niet meer; zie de accessor role() hieronder.
     */
    public function isSuperadmin(): bool
    {
        if (array_key_exists('is_superadmin', $this->attributes)) {
            return (bool) $this->attributes['is_superadmin'];
        }

        // Code al uitgerold, migratie nog niet gedraaid: de oude kolom is dan
        // nog de waarheid. Zonder deze terugval sluit de uitrol elke
        // superadmin zonder rol buiten tot iemand migrate draait.
        return ($this->attributes['role'] ?? null) === 'superadmin';
    }

    /** Superadmin, of minstens één gekoppelde rol. */
    public function hasPanelAccess(): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if (! $this->exists) {
            return false;
        }

        // Via de relatie en niet met roles()->exists(): Gate::before laadt
        // de rollen toch al, en publicShowable() roept dit per scope aan op
        // de front-end. Zo blijft het één query per gebruiker per verzoek.
        return $this->roles->isNotEmpty();
    }

    /**
     * Staat de kolom is_superadmin er al? Tussen het uitrollen van de code en
     * migrate niet; de scopes vallen dan terug op de oude kolom role, zodat
     * meldingen en opruimtaken in die periode niet op een onbekende kolom
     * crashen.
     */
    protected static function hasSuperadminColumn(): bool
    {
        return SchemaColumns::has((new static)->getTable(), 'is_superadmin');
    }

    /**
     * Wat iemand mag, als korte tekst voor schermen en meldingen:
     * "Superadmin", de namen van zijn rollen, of "Klant".
     */
    public function accessLabel(): string
    {
        if ($this->isSuperadmin()) {
            return __('Superadmin');
        }

        $names = $this->exists ? $this->roles->pluck('name')->filter()->all() : [];

        return $names !== [] ? implode(', ', $names) : __('Klant');
    }

    public function scopeSuperadmins(Builder $query): Builder
    {
        if (! static::hasSuperadminColumn()) {
            return $query->where($query->qualifyColumn('role'), 'superadmin');
        }

        return $query->where($query->qualifyColumn('is_superadmin'), true);
    }

    /** Iedereen met toegang tot het CMS: superadmin of minstens één rol. */
    public function scopePanelUsers(Builder $query): Builder
    {
        if (! static::hasSuperadminColumn()) {
            return $query->where(fn (Builder $q) => $q
                ->where($q->qualifyColumn('role'), 'superadmin')
                ->orWhereHas('roles'));
        }

        return $query->where(fn (Builder $q) => $q
            ->where($q->qualifyColumn('is_superadmin'), true)
            ->orWhereHas('roles'));
    }

    /** Klanten: geen superadmin en geen enkele rol. */
    public function scopeCustomers(Builder $query): Builder
    {
        if (! static::hasSuperadminColumn()) {
            return $query->where($query->qualifyColumn('role'), '!=', 'superadmin')
                ->whereDoesntHave('roles');
        }

        return $query->where(fn (Builder $q) => $q
            ->where($q->qualifyColumn('is_superadmin'), false)
            ->orWhereNull($q->qualifyColumn('is_superadmin')))
            ->whereDoesntHave('roles');
    }

    /**
     * Alleen voor achterwaartse compatibiliteit van klantprojecten en oude
     * fixtures: lezen geeft 'superadmin', 'admin' (heeft rollen) of
     * 'customer'; schrijven zet alleen de vlag ('superadmin' aan, al het
     * andere uit) en komt nooit als kolom in de insert. Nieuwe code gebruikt
     * dit nooit, maar isSuperadmin(), hasPanelAccess() en de scopes.
     *
     * @deprecated
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                // Installatie waar de migratie nog niet gedraaid heeft: de
                // oude kolom staat er nog, die is dan de waarheid.
                if (! array_key_exists('is_superadmin', $attributes) && is_string($value) && $value !== '') {
                    return $value;
                }

                if (! empty($attributes['is_superadmin'])) {
                    return 'superadmin';
                }

                return $this->hasPanelAccess() ? 'admin' : 'customer';
            },
            // Alleen 'superadmin' schrijft iets. Elke andere waarde laten we
            // liggen: oude klantcode die role = 'admin' of 'customer' zet op
            // een bestaande superadmin zou hem anders stil degraderen, langs
            // de grendels van het gebruikersscherm heen. Afschalen gaat
            // bewust via is_superadmin zelf.
            set: fn ($value) => $value === 'superadmin' ? ['is_superadmin' => true] : [],
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPanelAccess();
    }

    /**
     * Accounts met paneelrechten loggen uitsluitend via het paneel in (MFA,
     * IP-toegangslijst, inloglogboek). De frontend-login en -wachtwoordreset
     * doen auth()->login() op dezelfde guard en zouden dat allemaal omzeilen.
     */
    public function mustLoginViaPanel(): bool
    {
        return $this->hasPanelAccess();
    }

    /**
     * Mag $actor dit account overnemen (impersonate) of verwijderen?
     *
     * Nooit jezelf. Een account met toegang tot de beheeromgeving (dus ook
     * een superadmin) alleen door een superadmin: wie een beheerder kan
     * overnemen of weghalen, heeft zijn rechten. Een gewone klant blijft
     * voor iedereen met gebruikersrechten te beheren.
     */
    public function mayBeManagedBy(?self $actor): bool
    {
        if (! $actor || $actor->is($this)) {
            return false;
        }

        return $actor->isSuperadmin() || ! $this->mustLoginViaPanel();
    }

    /**
     * Voor stechstudio/filament-impersonate (en lab404): de ingelogde
     * gebruiker is de actor.
     */
    public function canBeImpersonated(): bool
    {
        $actor = auth()->user();

        return $actor instanceof self && $this->mayBeManagedBy($actor);
    }

    public function canImpersonate(): bool
    {
        return $this->mustLoginViaPanel();
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getNameAttribute()
    {
        if ($this->first_name || $this->last_name) {
            if ($this->first_name && $this->last_name) {
                return "$this->first_name $this->last_name";
            } elseif ($this->first_name) {
                return $this->first_name;
            } else {
                return $this->last_name;
            }
        } else {
            return $this->email;
        }
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(\Dashed\DashedEcommerceCore\Models\PriceGroup::class, 'price_group_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'dashed__model_has_roles', 'user_id', 'role_id');
    }

    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email)));
    }

    public function getAppAuthenticationSecret(): ?string
    {
        // This method should return the user's saved app authentication secret.

        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        // This method should save the user's app authentication secret.

        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        // In a user's authentication app, each account can be represented by a "holder name".
        // If the user has multiple accounts in your app, it might be a good idea to use
        // their email address as then they are still uniquely identifiable.

        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        // This method should return the user's saved app authentication recovery codes.

        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param  array<string> | null  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        // This method should save the user's app authentication recovery codes.

        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    public function hasEmailAuthentication(): bool
    {
        // E-mail is de standaard terugval-methode: zolang e-mail-2FA globaal aan
        // staat (standaard ja), is hij voor iedere beheerder beschikbaar, ook
        // zonder authenticator-app en zonder per-gebruiker opt-in. Zo wordt
        // niemand gedwongen eerst in de CMS een app in te stellen, en raakt een
        // beheerder zonder app niet buitengesloten. Alleen wanneer een beheerder
        // e-mail-2FA op alle sites uitzet, valt het terug op de per-gebruiker
        // kolom (nullable, vandaar de cast tegen een null-return).
        if (method_exists(cms(), 'emailMfaEnabled') && cms()->emailMfaEnabled()) {
            return true;
        }

        return (bool) $this->has_email_authentication;
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        // This method should save whether or not the user has enabled email authentication.

        $this->has_email_authentication = $condition;
        $this->save();
    }
}

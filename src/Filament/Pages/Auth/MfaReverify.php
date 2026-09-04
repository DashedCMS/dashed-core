<?php

namespace Dashed\DashedCore\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Illuminate\Contracts\Support\Htmlable;
use Dashed\DashedCore\Classes\MfaFreshness;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;

/**
 * Alleen het codeveld van het inloggen, voor een sessie waarvan de laatste
 * MFA-bevestiging verlopen is. Gebruikt dezelfde challenge-componenten en
 * dezelfde rate limiter als Filament's inlogpagina, zodat een code hier niet
 * zwakker gecontroleerd wordt dan daar.
 */
class MfaReverify extends SimplePage
{
    public const ROUTE = 'auth.mfa-reverify';

    /** @var array<string, mixed> | null */
    public ?array $data = [];

    /**
     * Geregistreerd via $panel->routes() en niet via ->pages(): een SimplePage
     * heeft geen eigen routes, en de route mag niet onder de auth-middleware
     * vallen waar EnsureMfaIsFresh in zit, anders stuurt die deze pagina naar
     * zichzelf. Ingelogd zijn wordt daarom hier in mount() gecontroleerd.
     */
    public static function getUrl(): string
    {
        return Filament::getCurrentOrDefaultPanel()->route(self::ROUTE);
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if (! $user) {
            redirect()->guest(Filament::getLoginUrl());

            return;
        }

        if (! MfaFreshness::needsReverification($user)) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        // Net als bij het inloggen krijgt alleen de eerste methode de
        // voorbereiding (de e-mailmethode stuurt daar zijn code).
        foreach (MfaFreshness::enabledProviders($user) as $provider) {
            if ($provider instanceof HasBeforeChallengeHook) {
                $provider->beforeChallenge($user);
            }

            break;
        }

        $this->form->fill();
    }

    public function getTitle(): string | Htmlable
    {
        return __('Bevestig je identiteit');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('Bevestig je identiteit');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('Je laatste bevestiging is verlopen. Vul je code in om verder te gaan.');
    }

    public function form(Schema $schema): Schema
    {
        $user = Filament::auth()->user();
        $providers = MfaFreshness::enabledProviders($user);
        $meerdere = count($providers) > 1;

        return $schema
            ->components([
                ...($meerdere ? [
                    Radio::make('provider')
                        ->label(__('Methode'))
                        ->options(array_map(
                            fn (MultiFactorAuthenticationProvider $provider): string => $provider->getLoginFormLabel(),
                            $providers,
                        ))
                        ->default(array_key_first($providers))
                        ->live()
                        // Bij het openen krijgt alleen de eerste methode de
                        // voorbereiding (zie mount). Wie overschakelt naar een
                        // methode die iets moet versturen, zoals de e-mailcode,
                        // krijgt die hier alsnog; net als op de inlogpagina.
                        ->afterStateUpdated(function (?string $state) use ($providers, $user): void {
                            $provider = $providers[$state] ?? null;

                            if ($provider instanceof HasBeforeChallengeHook) {
                                $provider->beforeChallenge($user);
                            }
                        }),
                ] : []),
                ...collect($providers)
                    ->map(fn (MultiFactorAuthenticationProvider $provider) => Group::make($provider->getChallengeFormComponents($user))
                        ->statePath($provider->getId())
                        ->when($meerdere, fn (Group $group) => $group->visible(fn (Get $get): bool => $get('provider') === $provider->getId())))
                    ->values()
                    ->all(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('verify')
                    ->footer([
                        Actions::make([
                            Action::make('verify')
                                ->label(__('Bevestigen'))
                                ->submit('verify'),
                        ])->fullWidth(),
                    ]),
            ]);
    }

    public function verify(): void
    {
        $user = Filament::auth()->user();

        // Dezelfde sleutel als Filament's inlogpagina, zodat raden hier en
        // daar tegen één teller telt.
        $key = "filament-multi-factor-challenge:{$user->getAuthIdentifier()}";

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            Notification::make()
                ->title(__('Te veel pogingen. Probeer het over :seconden seconden opnieuw.', ['seconden' => RateLimiter::availableIn($key)]))
                ->danger()
                ->send();

            return;
        }

        RateLimiter::hit($key);

        $this->form->validate();

        RateLimiter::clear($key);

        MfaFreshness::stamp();

        redirect()->intended(Filament::getUrl());
    }
}

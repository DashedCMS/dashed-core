<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Models\LoginAttempt;
use Illuminate\Support\Facades\Hash;
use Dashed\DashedCore\Classes\RateLimits;
use Dashed\DashedCore\Classes\AccountHelper;
use Illuminate\Validation\ValidationException;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedEcommerceCore\Classes\ShoppingCart;
use Dashed\DashedCore\Classes\Caching\IdentifiedVisitor;

class Login extends Component
{
    public ?string $loginEmail = '';

    public ?string $loginPassword = '';

    public ?bool $loginRememberMe = false;

    public ?string $registerEmail = '';

    public ?string $registerPassword = '';

    public ?string $registerPasswordConfirmation = '';

    public ?bool $registerRememberMe = false;

    public function mount()
    {
        if (auth()->check()) {
            return redirect(AccountHelper::getAccountUrl())->with('success', 'Je bent succesvol ingelogd');
        }
    }

    /**
     * Per IP en per e-mailadres, met het aantal uit Instellingen, Beveiliging
     * (limiter dashed-frontend-auth). Staat die op 0, dan telt hier niets.
     */
    private function ensureNotRateLimited(string $action, string $field): void
    {
        $seconds = RateLimits::hit('dashed-frontend-auth', $action . '|' . request()->ip());

        if ($seconds !== null) {
            throw ValidationException::withMessages([
                $field => ['Te veel pogingen, probeer het over ' . $seconds . ' seconden opnieuw.'],
            ]);
        }
    }

    public function login()
    {
        $this->ensureNotRateLimited('login:' . strtolower((string) $this->loginEmail), 'loginEmail');

        $this->validate(
            [
                'loginEmail' => [
                    'required',
                    'email',
                    'min:3',
                    'max:255',
                ],
                'loginPassword' => [
                    'required',
                    'min:6',
                    'max:255',
                ],
            ],
            [],
            [
                'loginEmail' => Translation::get('email', 'validation-attributes', 'email'),
                'loginPassword' => Translation::get('password', 'validation - attributes', 'password'),
            ]
        );

        $user = User::where('email', $this->loginEmail)->first();

        if (! $user) {
            return redirect()->back()->with('error', Translation::get('no-user-found', 'login', 'We could not find a user matching these criteria'));
        }

        if ($user->mustLoginViaPanel()) {
            return $this->refusePanelUser($user, 'security:frontend-login-refused');
        }

        if (! Hash::check($this->loginPassword, $user->password)) {
            return redirect()->back()->with('error', Translation::get('no-user-found', 'login', 'We could not find a user matching these criteria'));
        }

        auth()->login($user, $this->loginRememberMe);
        IdentifiedVisitor::mark();

        if (ShoppingCart::cartItemsCount() > 0) {
            return redirect(ShoppingCart::getCartUrl())->with('success', Translation::get('succesfully-logged-in', 'login', 'You are logged in!'));
        } else {
            return redirect(AccountHelper::getAccountUrl())->with('success', Translation::get('succesfully-logged-in', 'login', 'You are logged in!'));
        }
    }

    public function register()
    {
        $this->ensureNotRateLimited('register:' . strtolower((string) $this->registerEmail), 'registerEmail');

        $this->validate(
            [
                'registerEmail' => [
                    'unique:users,email',
                    'required',
                    'email:rfc',
                    'max:255',
                ],
                'registerPassword' => [
                    Password::defaults(),
                    'max:255',
                    'required',
                ],
                'registerPasswordConfirmation' => [
                    'max:255',
                    'required',
                    'same:registerPassword',
                ],
            ],
            [],
            [
                'registerEmail' => Translation::get('email', 'validation-attributes', 'email'),
                'registerPassword' => Translation::get('password', 'validation - attributes', 'password'),
                'registerPasswordConfirmation' => Translation::get('password-confirmation', 'validation - attributes', 'password confirmation'),
            ]
        );

        $user = new User();
        $user->email = $this->registerEmail;
        $user->password = Hash::make($this->registerPassword);
        $user->save();

        auth()->login($user, $this->registerRememberMe);
        IdentifiedVisitor::mark();

        return redirect(AccountHelper::getAccountUrl())->with('success', Translation::get('succesfully-logged-in', 'login', 'You are logged in!'));
    }

    /**
     * Beheerdersaccounts worden vóór de wachtwoordcontrole geweigerd, zodat de
     * frontend geen orakel is om beheerderswachtwoorden op te proberen. De
     * poging belandt wel in het inloglogboek en het activiteitenlogboek.
     */
    protected function refusePanelUser(User $user, string $description)
    {
        LoginAttempt::record(LoginAttempt::RESULT_FAILED, $user->email, $user);

        activity()
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
            ->log($description);

        return redirect()->route('filament.dashed.auth.login');
    }

    public function render()
    {
        return view(config('dashed-core.site_theme', 'dashed') . '.auth.login');
    }
}

<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Livewire\Component;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

    private function ensureNotRateLimited(string $action, string $field): void
    {
        $throttleKey = $action . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                $field => ['Te veel pogingen, probeer het over ' . $seconds . ' seconden opnieuw.'],
            ]);
        }

        RateLimiter::hit($throttleKey, 60);
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
                    'min:6',
                    'max:255',
                    'required',
                ],
                'registerPasswordConfirmation' => [
                    'min:6',
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

    public function render()
    {
        return view(config('dashed-core.site_theme', 'dashed') . '.auth.login');
    }
}

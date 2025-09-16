<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Livewire\Component;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Hash;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedEcommerceCore\Classes\ShoppingCart;

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

    public function login()
    {
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

        if (ShoppingCart::cartItemsCount() > 0) {
            return redirect(ShoppingCart::getCartUrl())->with('success', Translation::get('succesfully-logged-in', 'login', 'You are logged in!'));
        } else {
            return redirect(AccountHelper::getAccountUrl())->with('success', Translation::get('succesfully-logged-in', 'login', 'You are logged in!'));
        }
    }

    public function register()
    {
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

        return redirect(AccountHelper::getAccountUrl())->with('success', Translation::get('succesfully-logged-in', 'login', 'You are logged in!'));
    }

    public function render()
    {
        return view(config('dashed-core.site_theme') . '.auth.login');
    }
}

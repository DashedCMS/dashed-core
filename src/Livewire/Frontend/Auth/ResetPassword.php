<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Livewire\Component;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;

class ResetPassword extends Component
{
    public User $user;

    public ?string $password = '';

    public ?string $passwordConfirmation = '';

    public function mount(?string $passwordResetToken = null)
    {
        if (Auth::check()) {
            return redirect(AccountHelper::getAccountUrl())->with('success', 'Je bent succesvol ingelogd');
        }

        if (!$passwordResetToken) {
            abort(404);
        }

        $this->user = User::where('password_reset_token', $passwordResetToken)->first();
        if (! $this->user) {
            abort(404);
        }
    }

    public function submit()
    {
        $this->validate([
            'password' => [
                'min:6',
                'max:255',
                'required_with:passwordConfirmation',
                'same:passwordConfirmation',
            ],
        ]);

        $this->user->password_reset_token = null;
        $this->user->password_reset_requested = null;
        $this->user->password = Hash::make($this->password);
        $this->user->save();

        Auth::login($this->user);

        return redirect(route('dashed.frontend.account'))->with('success', Translation::get('reset-password-post-success', 'login', 'Your password has been reset!'));
    }

    public function render()
    {
        return view(env('SITE_THEME', 'dashed') . '.auth.reset-password');
    }
}

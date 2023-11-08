<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Mail\PasswordResetMail;
use Dashed\DashedTranslations\Models\Translation;

class ForgotPassword extends Component
{
    public ?string $email = '';

    public function submit()
    {
        $this->validate([
            'email' => [
                'required',
                'email:rfc',
                'max:255',
            ],
        ]);

        $user = User::where('email', $this->email)->first();
        if ($user) {
            $user->password_reset_token = Str::random(64);
            $user->password_reset_requested = Carbon::now();
            $user->save();
            Mail::to($user->email)->send(new PasswordResetMail($user));
        }

        $this->reset('email');

        $this->dispatch('showAlert', 'success', Translation::get('forgot-password-post-success', 'login', 'If we can find an account with your email you will receive a email to reset your password.'));
    }

    public function render()
    {
        return view('dashed-ecommerce-core::frontend.auth.forgot-password');
    }
}

<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Exception;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedCore\Mail\PasswordResetMail;
use Dashed\DashedTranslations\Models\Translation;

class ForgotPassword extends Component
{
    public ?string $email = '';

    public function mount()
    {
        if (auth()->check()) {
            return redirect(AccountHelper::getAccountUrl())->with('success', 'Je bent succesvol ingelogd');
        }
    }

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

            try {
                Mail::to($user->email)->send(new PasswordResetMail($user));
            } catch (Exception $e) {

            }
        }

        $this->reset('email');

        Notification::make()
            ->success()
            ->body(Translation::get('forgot-password-post-success', 'login', 'Als we een account gekoppeld aan het ingevulde emailadres vinden, sturen we je een mail om je wachtwoord te resetten.'))
            ->send();
        $this->dispatch('showAlert', 'success', Translation::get('forgot-password-post-success', 'login', 'Als we een account gekoppeld aan het ingevulde emailadres vinden, sturen we je een mail om je wachtwoord te resetten.'));
    }

    public function render()
    {
        return view(config('dashed-core.site_theme') . '.auth.forgot-password');
    }
}

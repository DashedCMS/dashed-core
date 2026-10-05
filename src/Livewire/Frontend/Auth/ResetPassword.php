<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Livewire\Component;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedCore\Classes\Caching\IdentifiedVisitor;

class ResetPassword extends Component
{
    public User $user;

    public ?string $password = '';

    public ?string $passwordConfirmation = '';

    public function mount(?string $passwordResetToken = null)
    {
        if (auth()->check()) {
            return redirect(AccountHelper::getAccountUrl())->with('success', 'Je bent succesvol ingelogd');
        }

        if (! $passwordResetToken) {
            $passwordResetToken = request()->query('passwordResetToken');
        }

        // Eerst in een lokale variabele: `$this->user` is niet-nullable getypeerd, dus een
        // onbekend token gaf een TypeError (500) nog voordat de controle eronder kon ingrijpen.
        $user = $passwordResetToken
            ? User::where('password_reset_token', $passwordResetToken)->first()
            : null;

        // Dezelfde TTL als de geplande opschoon-taak (1 uur), maar nu ook afgedwongen
        // bij het inwisselen zodat een token niet langer geldig is dan bedoeld.
        if (! $user
            || ! $user->password_reset_requested
            || \Illuminate\Support\Carbon::parse($user->password_reset_requested)->lt(\Illuminate\Support\Carbon::now()->subHour())) {
            return redirect(AccountHelper::getForgotPasswordUrl())->with('error', Translation::get('reset-password-link-invalid', 'login', 'This password reset link is invalid or has expired. Request a new one.'));
        }

        $this->user = $user;

        if ($this->user->mustLoginViaPanel()) {
            return $this->refusePanelUser();
        }
    }

    public function submit()
    {
        if ($this->user->mustLoginViaPanel()) {
            return $this->refusePanelUser();
        }

        $this->validate([
            'password' => [
                Password::defaults(),
                'max:255',
                'required_with:passwordConfirmation',
                'same:passwordConfirmation',
            ],
        ]);

        $this->user->password_reset_token = null;
        $this->user->password_reset_requested = null;
        $this->user->password = Hash::make($this->password);
        $this->user->save();

        auth()->login($this->user);
        IdentifiedVisitor::mark();

        return redirect(AccountHelper::getAccountUrl())->with('success', Translation::get('reset-password-post-success', 'login', 'Your password has been reset!'));
    }

    /**
     * De frontend-reset logt direct in en omzeilt zo MFA en de IP-toegangslijst.
     * Beheerdersaccounts resetten uitsluitend via het paneel.
     */
    protected function refusePanelUser()
    {
        activity()
            ->performedOn($this->user)
            ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
            ->log('security:frontend-password-reset-refused');

        return redirect()->route('filament.dashed.auth.login');
    }

    public function render()
    {
        return view(config('dashed-core.site_theme', 'dashed') . '.auth.reset-password');
    }
}

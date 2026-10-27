<?php

namespace Dashed\DashedCore\Livewire\Frontend\Auth;

use Livewire\Component;
use Livewire\Attributes\Locked;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedCore\Classes\Caching\IdentifiedVisitor;

class ResetPassword extends Component
{
    #[Locked]
    public User $user;

    /**
     * Het token uit de link. Bij het opslaan wordt het opnieuw opgezocht en
     * op geldigheid getoetst, want tussen openen en opslaan kan het gebruikt,
     * ingetrokken of verlopen zijn.
     */
    #[Locked]
    public string $resetToken = '';

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
        $user = static::userForToken($passwordResetToken);

        if (! $user) {
            return $this->refuseInvalidToken();
        }

        $this->user = $user;
        $this->resetToken = (string) $passwordResetToken;

        if ($this->user->mustLoginViaPanel()) {
            return $this->refusePanelUser();
        }
    }

    public function submit()
    {
        $user = static::userForToken($this->resetToken);

        if (! $user || ! $user->is($this->user)) {
            return $this->refuseInvalidToken();
        }

        $this->user = $user;

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
     * De gebruiker bij een nog geldig token, of null. Dezelfde TTL als de
     * geplande opschoon-taak (1 uur), afgedwongen bij openen en bij opslaan
     * zodat een token niet langer geldig is dan bedoeld.
     */
    protected static function userForToken(?string $token): ?User
    {
        if (! $token) {
            return null;
        }

        $user = User::where('password_reset_token', $token)->first();

        if (! $user
            || ! hash_equals((string) $user->password_reset_token, $token)
            || ! $user->password_reset_requested
            || \Illuminate\Support\Carbon::parse($user->password_reset_requested)->lt(\Illuminate\Support\Carbon::now()->subHour())) {
            return null;
        }

        return $user;
    }

    protected function refuseInvalidToken()
    {
        return redirect(AccountHelper::getForgotPasswordUrl())->with('error', Translation::get('reset-password-link-invalid', 'login', 'This password reset link is invalid or has expired. Request a new one.'));
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

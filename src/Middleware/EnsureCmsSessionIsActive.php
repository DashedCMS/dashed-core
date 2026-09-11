<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Models\LoginAttempt;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\CmsIdleTimeout;
use Dashed\DashedCore\Classes\CmsSessionLimits;

/**
 * Persistente paneelmiddleware, na de sessie en voor de authenticatie van de
 * pagina: loopt dus ook op de Livewire-verzoeken van een open pagina. Wie
 * langer dan de termijn niets deed wordt uitgelogd en naar het inlogscherm
 * gestuurd; elk ander verzoek dat als activiteit telt zet de klok terug.
 * Zie CmsIdleTimeout voor wat als activiteit telt.
 */
class EnsureCmsSessionIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Filament::auth();
        $user = $guard->user();

        if (! $user) {
            return $next($request);
        }

        // Een login via het onthoud-mij-cookie slaat de MFA-uitdaging over en
        // is voor een paneelaccount daarom geen login. Zie CmsSessionLimits.
        if (method_exists($guard, 'viaRemember') && $guard->viaRemember() && $this->isPanelUser($user)) {
            return $this->logout($request, $user, LoginAttempt::RESULT_REMEMBER_REJECTED, __('Log opnieuw in: een sessie via het onthoud-mij-cookie is voor beheerders niet toegestaan.'));
        }

        // Een sessie van voor deze functie heeft nog geen begin: dit verzoek
        // is dan het begin.
        if (! CmsSessionLimits::authenticatedAt()) {
            CmsSessionLimits::stamp();
        }

        if (CmsSessionLimits::maxIsExceeded()) {
            return $this->logout($request, $user, LoginAttempt::RESULT_SESSION_EXPIRED, __('Je sessie is verlopen na :minuten minuten; log opnieuw in.', ['minuten' => CmsSessionLimits::maxMinutes()]));
        }

        if (! CmsIdleTimeout::isEnabled()) {
            return $next($request);
        }

        if (CmsIdleTimeout::isExpired()) {
            return $this->logout($request, $user, LoginAttempt::RESULT_IDLE_LOGOUT, __('Je bent automatisch uitgelogd na :minuten minuten zonder activiteit.', ['minuten' => CmsIdleTimeout::minutes()]));
        }

        if (CmsIdleTimeout::isActivity($request->hasHeader('X-Livewire'), (array) $request->json()->all())) {
            CmsIdleTimeout::touch();
        }

        return $next($request);
    }

    protected function isPanelUser($user): bool
    {
        return $user instanceof User ? $user->mustLoginViaPanel() : true;
    }

    protected function logout(Request $request, $user, string $result, string $message)
    {
        LoginAttempt::record($result, $user->email ?? null, $user instanceof User ? $user : null);

        Filament::auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Notification::make()
            ->title($message)
            ->warning()
            ->send();

        return redirect()->guest(Filament::getLoginUrl());
    }
}

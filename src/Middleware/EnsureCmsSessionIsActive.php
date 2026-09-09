<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Models\LoginAttempt;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\CmsIdleTimeout;

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
        $user = Filament::auth()->user();

        if (! $user || ! CmsIdleTimeout::isEnabled()) {
            return $next($request);
        }

        if (CmsIdleTimeout::isExpired()) {
            return $this->logout($request, $user);
        }

        if (CmsIdleTimeout::isActivity($request->hasHeader('X-Livewire'), (array) $request->json()->all())) {
            CmsIdleTimeout::touch();
        }

        return $next($request);
    }

    protected function logout(Request $request, $user)
    {
        $minutes = CmsIdleTimeout::minutes();

        LoginAttempt::record(LoginAttempt::RESULT_IDLE_LOGOUT, $user->email ?? null, $user instanceof User ? $user : null);

        Filament::auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Notification::make()
            ->title(__('Je bent automatisch uitgelogd na :minuten minuten zonder activiteit.', ['minuten' => $minutes]))
            ->warning()
            ->send();

        return redirect()->guest(Filament::getLoginUrl());
    }
}

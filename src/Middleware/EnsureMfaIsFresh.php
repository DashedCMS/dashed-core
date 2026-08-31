<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Dashed\DashedCore\Classes\MfaFreshness;
use Dashed\DashedCore\Filament\Pages\Auth\MfaReverify;

/**
 * Staat in de auth-middleware van het paneel, na Authenticate. Is de laatste
 * MFA-bevestiging ouder dan de ingestelde termijn (of is er geen), dan gaat
 * de gebruiker eerst langs de bevestigingspagina en daarna terug naar waar
 * hij heen wilde. Uitloggen mag altijd: wie zijn code kwijt is moet er nog
 * uit kunnen.
 */
class EnsureMfaIsFresh
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('filament.*.auth.logout')) {
            return $next($request);
        }

        if (MfaFreshness::needsReverification(Filament::auth()->user())) {
            return redirect()->guest(MfaReverify::getUrl());
        }

        return $next($request);
    }
}

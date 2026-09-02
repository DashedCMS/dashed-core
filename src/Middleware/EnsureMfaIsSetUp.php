<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

/**
 * Staat in de auth-middleware van het paneel, vóór EnsureMfaIsFresh. Is MFA
 * verplicht (force_mfa) en heeft de ingelogde gebruiker nog geen enkele
 * methode ingesteld, dan gaat elke paginalading eerst langs Filaments
 * instelpagina, tot het geregeld is.
 *
 * Waarom niet Filaments eigen afdwinging: die middleware wordt per pagina aan
 * de route gehangen op het moment van routeregistratie (HasRoutes), dus een
 * routecache bevriest de stand van de schakelaar en pagina's buiten dat
 * mechanisme vallen erbuiten. Deze middleware leest de schakelaar per verzoek.
 *
 * Uitloggen mag altijd, en de instelpagina zelf natuurlijk ook, anders wijst
 * de omleiding naar zichzelf.
 */
class EnsureMfaIsSetUp
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs(
            'filament.*.auth.logout',
            'filament.*.auth.multi-factor-authentication.*',
        )) {
            return $next($request);
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel || ! $panel->isMultiFactorAuthenticationRequired()) {
            return $next($request);
        }

        $user = Filament::auth()->user();
        $providers = Filament::getMultiFactorAuthenticationProviders();

        // Zonder gebruiker valt hier niets af te dwingen, en zonder methodes
        // heeft de instelpagina niets aan te bieden; dan liever doorlaten dan
        // een omleiding die nergens heen kan.
        if (! $user || $providers === []) {
            return $next($request);
        }

        foreach ($providers as $provider) {
            if ($provider->isEnabled($user)) {
                return $next($request);
            }
        }

        // Met een verouderde routecache (verplichting aangezet ná het cachen)
        // bestaat de instelpagina nog niet; dan kan er niets afgedwongen
        // worden tot de cache opnieuw is opgebouwd.
        if (! Route::has($panel->getSetUpRequiredMultiFactorAuthenticationRouteName())) {
            return $next($request);
        }

        return redirect()->guest($panel->getSetUpRequiredMultiFactorAuthenticationUrl());
    }
}

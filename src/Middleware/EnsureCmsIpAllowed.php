<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Dashed\DashedCore\Models\LoginAttempt;
use Dashed\DashedCore\Classes\CmsIpAllowlist;

/**
 * Staat in de middleware van het paneel, vóór authenticatie, en is persistent
 * zodat ook de Livewire-verzoeken van een al geopende pagina eronder vallen.
 * Anders zou een sessie die vanaf een toegestaan adres is begonnen elders
 * gewoon doorwerken, en dat is niet wat "alleen vanaf deze adressen" betekent.
 */
class EnsureCmsIpAllowed
{
    public function handle(Request $request, Closure $next)
    {
        if (! CmsIpAllowlist::allows($request->ip())) {
            // Alleen de inlogroute: wie daar klopt vanaf een vreemd adres wil
            // je terugzien in het inloglogboek. Elke andere paneelroute zou
            // per scanner tientallen regels opleveren.
            if ($request->routeIs('filament.*.auth.login')) {
                LoginAttempt::record(LoginAttempt::RESULT_IP_BLOCKED, null);
            }

            abort(403, __('Het CMS is niet bereikbaar vanaf dit IP-adres.'));
        }

        return $next($request);
    }
}

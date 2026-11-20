<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Dashed\DashedCore\Classes\NavigationUsage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telt elke paginalading in het paneel mee voor het menu-item waar hij onder
 * valt (zie NavigationUsage). Hangt in de auth-middleware, die niet
 * persistent is: Livewire-verzoeken van een al geopende pagina komen hier
 * niet langs, en dat is precies goed, want die zijn geen bezoek.
 */
class RecordNavigationUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->isMethod('GET')
            && ! $request->hasHeader('X-Livewire')
            && ! $request->expectsJson()
            && $response->getStatusCode() === 200
            && ($user = $request->user())
        ) {
            NavigationUsage::record($user, $request);
        }

        return $response;
    }
}

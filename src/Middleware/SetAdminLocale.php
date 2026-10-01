<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Dashed\DashedCore\Classes\AdminLocale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persistente paneelmiddleware: draait ook op Livewire-verzoeken van een al
 * geopende pagina, anders springt elke klik terug naar de standaardtaal. De
 * persistente stapel draait voor authMiddleware, dus de gebruiker komt hier
 * rechtstreeks uit de sessie-guard; niet ingelogd (inlogpagina) geeft de
 * standaard.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        AdminLocale::apply(auth()->guard('web')->user());

        return $next($request);
    }
}

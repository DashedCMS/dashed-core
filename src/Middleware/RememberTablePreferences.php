<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Dashed\DashedCore\Classes\TablePreferences;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persistente paneelmiddleware, want sorteren, filteren en kolommen wisselen
 * zijn Livewire-verzoeken van een al geopende pagina. Zie
 * {@see TablePreferences} voor het waarom.
 *
 * Wegschrijven gebeurt alleen als na het verzoek dezelfde gebruiker nog
 * ingelogd is en de sessie de markering nog draagt: uitloggen leegt de
 * sessie, en dat mag niet lezen als "alle tabelinstellingen gewist".
 */
class RememberTablePreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = auth()->guard('web')->id();

        if ($userId === null || ! $request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();

        rescue(fn () => TablePreferences::restore($session, $userId), report: false);

        $before = TablePreferences::snapshot($session);

        $response = $next($request);

        if (
            auth()->guard('web')->id() === $userId &&
            (string) $session->get(TablePreferences::LOADED_MARKER) === (string) $userId
        ) {
            rescue(
                fn () => TablePreferences::persist($userId, $before, TablePreferences::snapshot($session)),
                report: false,
            );
        }

        return $response;
    }
}

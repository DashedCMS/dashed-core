<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Goedkope afhandeling van verzoeken die nooit een pagina kunnen zijn, zoals
 * scanners op /wp-admin of /.env en ontbrekende assets. Die kostten via de
 * fallback 15 tot 25 queries plus een render met thema.
 *
 * Hangt alleen aan de fallback, als eerste middleware en dus vóór de sessie.
 * De globale middleware (TrustedHosts, SecurityHeaders) loopt er gewoon omheen.
 * Deze verzoeken komen bewust niet in het 404-logboek.
 */
class RejectNonPageRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = trim(rawurldecode($request->path()), '/');
        $withoutLocale = $this->withoutLocale($path);

        // De router verkiest de fallback boven een 405 op een POST-route.
        if ($this->isLivewireUpdatePath($withoutLocale)) {
            return response('', Response::HTTP_METHOD_NOT_ALLOWED, ['Allow' => 'POST']);
        }

        if (! config('dashed-core.non_page_requests.enabled', true) || ! $this->isNonPagePath($withoutLocale)) {
            return $next($request);
        }

        // Een bestaande redirect gaat voor, ook op een pad als /oud.php. Valt de
        // database weg, dan is een 404 nog steeds het juiste antwoord.
        $redirect = rescue(fn () => Redirect::findForSlugs([$withoutLocale, $path]), null, false);

        if ($redirect) {
            return new RedirectResponse(url($redirect->to), $redirect->sort);
        }

        return response('Not found', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function isNonPagePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // Een pad met een verborgen segment (.env, .git/config) is nooit een slug.
        if (preg_match('#(^|/)\.#', $path) === 1) {
            return true;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension !== '' && in_array($extension, config('dashed-core.non_page_requests.extensions', []), true)) {
            return true;
        }

        // Op hele segmenten, zodat een pagina /actuatoren een pagina blijft.
        foreach (config('dashed-core.non_page_requests.path_prefixes', []) as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isLivewireUpdatePath(string $path): bool
    {
        if (! str_ends_with($path, 'update')) {
            return false;
        }

        $updatePath = rescue(fn () => trim(app('livewire')->getUpdateUri(), '/'), 'livewire/update', false);

        return $path === $updatePath;
    }

    private function withoutLocale(string $path): string
    {
        foreach (Locales::getLocales() as $locale) {
            $prefix = ($locale['id'] ?? '') . '/';

            if ($prefix !== '/' && str_starts_with($path, $prefix)) {
                return substr($path, strlen($prefix));
            }
        }

        return $path;
    }
}

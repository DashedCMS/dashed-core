<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\LaravelFlare\Facades\Flare;

class AddLivewireReferrerToFlareMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('livewire/update')) {
            $referer = $request->headers->get('referer');

            if ($referer) {
                Flare::context('livewire_referer', $referer);
                Flare::context('livewire_uri', $request->getRequestUri());
            }
        }

        $response = $next($request);

        return $response;
    }
}

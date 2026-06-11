<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/'.config('filament.path').'/login')->with('error', 'Je moet ingelogd zijn om deze pagina te bezoeken');
        }

        // Alleen back-office gebruikers (admin/superadmin of een gekoppelde rol) mogen door.
        // Zelf-geregistreerde webshop-klanten hebben hier niets te zoeken.
        if (! (in_array($user->role, ['superadmin', 'admin'], true) || $user->roles->isNotEmpty())) {
            abort(403);
        }

        return $next($request);
    }
}

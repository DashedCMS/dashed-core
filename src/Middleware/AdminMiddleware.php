<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/' . config('qcommerce.path') . '/login')->with('error', 'Je moet ingelogd zijn om deze pagina te bezoeken');
        }

        if (Auth::user()->role != 'admin') {
            return redirect('/')->with('error', 'Je moet ingelogd zijn om deze pagina te bezoeken');
        }

        return $next($request);
    }
}

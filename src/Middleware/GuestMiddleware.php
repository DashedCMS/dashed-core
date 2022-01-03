<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Qubiqx\Qcommerce\Classes\Webshop;
use Qubiqx\QcommerceCore\Classes\AccountHelper;

class GuestMiddleware
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
        if (Auth::check() && Auth::user()->role == 'admin') {
            return redirect('/' . config('filament.path') . '/dashboard')->with('success', 'Je bent succesvol ingelogd');
        } elseif (Auth::check() && Auth::user()->role != 'admin') {
            return redirect(AccountHelper::getAccountUrl())->with('success', 'Je bent succesvol ingelogd');
        }

        return $next($request);
    }
}

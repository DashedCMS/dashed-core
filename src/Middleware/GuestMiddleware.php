<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Dashed\DashedCore\Classes\AccountHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role == 'admin') {
            return redirect('/'.config('filament.path').'/dashboard')->with('success', 'Je bent succesvol ingelogd');
        } elseif (Auth::check() && Auth::user()->role != 'admin') {
            return redirect(AccountHelper::getAccountUrl())->with('success', 'Je bent succesvol ingelogd');
        }

        return $next($request);
    }
}

<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Dashed\DashedCore\Classes\AccountHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guest()) {
            return redirect(AccountHelper::getLogoutUrl())->with('success', 'Je bent nog niet ingelogd');
        }

        return $next($request);
    }
}

<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Qubiqx\Qcommerce\Classes\Webshop;
use Qubiqx\QcommerceCore\Classes\AccountHelper;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
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

<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Http\RedirectResponse;
use Mcamara\LaravelLocalization\LanguageNegotiator;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationMiddlewareBase;

class LocaleSessionRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $params = explode('/', $request->path());
        $locale = session('locale', false);

        if (\count($params) > 0 && app('laravellocalization')->checkLocaleInSupportedLocales($params[0])) {
            session(['locale' => $params[0]]);
            Locales::setLocale($params[0]);

            if($params[0] == app('laravellocalization')->getDefaultLocale() && app('laravellocalization')->isHiddenDefault($params[0])){
                return redirect()->to(app('laravellocalization')->getNonLocalizedURL());
            }

            return $next($request);
        }elseif (\count($params) > 0 && !app('laravellocalization')->checkLocaleInSupportedLocales($params[0])) {
            session(['locale' => app('laravellocalization')->getDefaultLocale()]);
            Locales::setLocale(app('laravellocalization')->getDefaultLocale());

            return $next($request);
        }

        if (empty($locale) && app('laravellocalization')->hideUrlAndAcceptHeader()) {
            // When default locale is hidden and accept language header is true,
            // then compute browser language when no session has been set.
            // Once the session has been set, there is no need
            // to negotiate language from browser again.
            $negotiator = new LanguageNegotiator(
                app('laravellocalization')->getDefaultLocale(),
                app('laravellocalization')->getSupportedLocales(),
                $request
            );
            $locale = $negotiator->negotiateLanguage();
            session(['locale' => $locale]);
        }


        if ($locale === false) {
            $locale = app('laravellocalization')->getCurrentLocale();
        }

        if (
            $locale &&
            app('laravellocalization')->checkLocaleInSupportedLocales($locale) &&
            !(app('laravellocalization')->isHiddenDefault($locale))
        ) {
            app('session')->reflash();
            $redirection = app('laravellocalization')->getLocalizedURL($locale);

            return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
        }

        return $next($request);
    }
}

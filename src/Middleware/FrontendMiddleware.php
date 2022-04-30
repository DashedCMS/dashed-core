<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class FrontendMiddleware
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
        App::setLocale(LaravelLocalization::getCurrentLocale());

        //Todo: make sure stuff below works
        //Todo: create JSON schema's
        config([
            'seotools.meta.defaults.title' => Customsetting::get('site_name', Sites::getActive(), 'Website'),
            'seotools.meta.defaults.separator' => ' | ',
            'seotools.meta.defaults.description' => '',
            'seotools.opengraph.defaults.description' => '',
            'seotools.json-ld.defaults.description' => '',
            'seotools.meta.defaults.robots' => env('APP_ENV') == 'local' ? 'noindex, nofollow' : 'index, follow',
            'seotools.meta.webmaster_tags.google' => Customsetting::get('webmaster_tag_google', Sites::getActive(), ''),
            'seotools.meta.webmaster_tags.bing' => Customsetting::get('webmaster_tag_bing', Sites::getActive(), ''),
            'seotools.meta.webmaster_tags.alexa' => Customsetting::get('webmaster_tag_alexa', Sites::getActive(), ''),
            'seotools.meta.webmaster_tags.pinterest' => Customsetting::get('webmaster_tag_pinterest', Sites::getActive(), ''),
            'seotools.meta.webmaster_tags.yandex' => Customsetting::get('webmaster_tag_yandex', Sites::getActive(), ''),
            'seotools.meta.webmaster_tags.norton' => Customsetting::get('webmaster_tag_norton', Sites::getActive(), ''),
        ]);

        $logo = Customsetting::get('site_logo', Sites::getActive(), '');
        $favicon = Customsetting::get('site_favicon', Sites::getActive(), '');

        View::share('logo', $logo);
        View::share('favicon', $favicon);

        return $next($request);
    }
}

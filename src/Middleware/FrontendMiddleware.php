<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Qubiqx\QcommerceCore\Classes\Sites;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Qubiqx\QcommerceCore\Models\Customsetting;

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
        config([
            'seotools.meta.defaults.title' => Customsetting::get('store_name', Sites::getActive(), 'Website'),
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

        $storeMedia = Cache::tags(['general-settings'])->rememberForever("store-media", function () {
            $store = Customsetting::where('name', 'store_name')->with(['media'])->thisSite()->first();
            if ($store) {
                $logo = $store->getFirstMedia('logo');
                $favicon = $store->getFirstMedia('favicon');
            } else {
                $logo = '';
                $favicon = '';
            }

            return [
                'logo' => $logo,
                'favicon' => $favicon,
            ];
        });

        View::share('logo', $storeMedia['logo'] ?? '');
        View::share('favicon', $storeMedia['favicon'] ?? '');

        return $next($request);
    }
}

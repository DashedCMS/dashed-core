<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\SchemaOrg\Schema;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Classes\Locales;
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
        Locales::setLocale(LaravelLocalization::getCurrentLocale());

        seo()->metaData('webmasterTags', [
            'google' => Customsetting::get('webmaster_tag_google'),
            'bing' => Customsetting::get('webmaster_tag_bing'),
            'alexa' => Customsetting::get('webmaster_tag_alexa'),
            'pinterest' => Customsetting::get('webmaster_tag_pinterest'),
            'yandex' => Customsetting::get('webmaster_tag_yandex'),
            'norton' => Customsetting::get('webmaster_tag_norton'),
        ]);
        seo()->metaData('robots', env('APP_ENV') == 'local' ? 'noindex, nofollow' : 'index, follow');
        seo()->metaData('metaTitle', Customsetting::get('site_name', Sites::getActive(), 'Website'));
        if(!seo()->metaData('metaImage')){
            seo()->metaData('metaImage', Customsetting::get('site_logo', Sites::getActive(), ''));
        }

        $logo = Customsetting::get('site_logo', Sites::getActive(), '');
        $favicon = Customsetting::get('site_favicon', Sites::getActive(), '');

        seo()->metaData('schemas', [
            'localBusiness' => Schema::localBusiness()
                ->legalName(Customsetting::get('site_name'))
                ->email(Customsetting::get('site_to_email'))
                ->telephone(Customsetting::get('company_phone_number'))
                ->logo($logo)
                ->address(Customsetting::get('company_street') . ' ' . Customsetting::get('company_street_number') . ', ' . Customsetting::get('company_postal_code') . ' ' . Customsetting::get('company_city') . ', ' . Customsetting::get('company_country'))
                ->url($request->url())
                ->contactPoint(
                    Schema::contactPoint()
                        ->telephone(Customsetting::get('company_phone_number'))
                        ->email(Customsetting::get('site_to_email'))
                ),
        ]);

        View::share('logo', $logo);
        View::share('favicon', $favicon);

        return $next($request);
    }
}

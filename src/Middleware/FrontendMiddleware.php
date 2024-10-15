<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\SchemaOrg\Schema;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\View;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Customsetting;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class FrontendMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (preg_match('/.+\/$/', $request->getRequestUri())) {
            $url = rtrim($request->getRequestUri(), '/');

            return redirect($url, 301);
        }

        //        if (!Customsetting::get('force_trailing_slash', null, false) && str($_SERVER['REQUEST_URI'])->endsWith('/')) {
        //            return redirect(str($_SERVER['REQUEST_URI'])->replaceLast('/', ''));
        //        }elseif(Customsetting::get('force_trailing_slash', null, false) && !str($_SERVER['REQUEST_URI'])->endsWith('/')){
        //            return redirect(str($_SERVER['REQUEST_URI'])->append('/'));
        //        }

        //        Locales::setLocale(LaravelLocalization::getCurrentLocale());

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
        if (! seo()->metaData('metaImage') && Customsetting::get('default_meta_data_image', Sites::getActive(), '')) {
            seo()->metaData('metaImage', Customsetting::get('default_meta_data_image', Sites::getActive(), ''));
        }

        $logo = mediaHelper()->getSingleMedia(Customsetting::get('site_logo', Sites::getActive(), ''), 'thumb');
        $favicon = mediaHelper()->getSingleMedia(Customsetting::get('site_favicon', Sites::getActive(), ''), 'thumb');

        $schema = Schema::organization()
            ->identifier(request()->url() . '#Organization')
            ->legalName(Customsetting::get('site_name'))
            ->email(Customsetting::get('site_to_email'))
            ->telephone(Customsetting::get('company_phone_number'))
            ->logo($logo->url ?? '')
            ->address(Customsetting::get('company_street') . ' ' . Customsetting::get('company_street_number') . ', ' . Customsetting::get('company_postal_code') . ' ' . Customsetting::get('company_city') . ', ' . Customsetting::get('company_country'))
            ->addProperties([
                'address' => [
                    'streetAddress' => Customsetting::get('company_street') . ' ' . Customsetting::get('company_street_number'),
                    'postalCode' => Customsetting::get('company_postal_code'),
                    'addressCountry' => Customsetting::get('company_country'),
                ],
            ])
            ->url($request->url())
            ->contactPoint(
                Schema::contactPoint()
                    ->telephone(Customsetting::get('company_phone_number'))
                    ->email(Customsetting::get('site_to_email'))
            );

        if (Customsetting::get('google_maps_reviews_synced', false)) {
            $schema->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue(Customsetting::get('google_maps_rating'))
                    ->bestRating(5)
                    ->worstRating(1)
                    ->reviewCount(Customsetting::get('google_maps_review_count'))
                    ->url('https://www.google.com/')
            );
        }

        seo()->metaData('schemas', array_merge([
            'localBusiness' => $schema,
        ], seo()->metaData('schemas')));

        View::share('logo', $logo);
        View::share('favicon', $favicon);

        return $next($request);
    }
}

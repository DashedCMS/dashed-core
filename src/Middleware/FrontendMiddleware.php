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
use Dashed\LaravelLocalization\Facades\LaravelLocalization;

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
        seo()->metaData('robots', app()->isLocal() ? 'noindex, nofollow' : 'index, follow');
        seo()->metaData('metaTitle', Customsetting::get('site_name', Sites::getActive(), 'Website'));
        if (! seo()->metaData('metaImage') && Customsetting::get('default_meta_data_image', Sites::getActive(), '')) {
            seo()->metaData('metaImage', mediaHelper()->getSingleMedia(Customsetting::get('default_meta_data_image', Sites::getActive(), ''))->url ?? '');
        }

        $logo = Customsetting::get('site_logo', Sites::getActive(), '');
        $favicon = Customsetting::get('site_favicon', Sites::getActive(), '');

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

        $googleTagmanagerId = Customsetting::get('google_tagmanager_id', null);
        $triggerTikTokEvents = Customsetting::get('trigger_tiktok_events', null, false);
        $facebookPixelConversionId = Customsetting::get('facebook_pixel_conversion_id', null);
        $facebookPixelSiteId = Customsetting::get('facebook_pixel_site_id', null);
        $triggerFacebookEvents = Customsetting::get('trigger_facebook_events', null, false);
        $googleMerchantCenterId = Customsetting::get('google_merchant_center_id', null);
        $enableGoogleMerchantReviewSurvey = Customsetting::get('enable_google_merchant_center_review_survey', null, false);
        $extraBodyScripts = Customsetting::get('extra_body_scripts', null, '');
        $enableGoogleMerchantReviewBadge = Customsetting::get('enable_google_merchant_center_review_badge', null, false);
        $siteName = Customsetting::get('site_name', null, 'Website');
        $googleAnalyticsId = Customsetting::get('google_analytics_id', null);
        $extraScripts = Customsetting::get('extra_scripts', null, '');

        View::share('trackingSettings', [
            'google_tagmanager_id' => $googleTagmanagerId,
            'trigger_tiktok_events' => (bool) $triggerTikTokEvents,
            'facebook_pixel_conversion_id' => $facebookPixelConversionId,
            'facebook_pixel_site_id' => $facebookPixelSiteId,
            'trigger_facebook_events' => (bool) $triggerFacebookEvents,
            'google_merchant_center_id' => $googleMerchantCenterId,
            'enable_google_merchant_center_review_survey' => (bool) $enableGoogleMerchantReviewSurvey,
            'enable_google_merchant_center_review_badge' => (bool) $enableGoogleMerchantReviewBadge,
            'google_analytics_id' => $googleAnalyticsId,
        ]);
        View::share('extraBodyScripts', $extraBodyScripts);

        View::share('logo', $logo);
        View::share('favicon', $favicon);
        View::share('extraHeadScripts', $extraScripts);
        View::share('siteName', $siteName);

        return $next($request);
    }
}

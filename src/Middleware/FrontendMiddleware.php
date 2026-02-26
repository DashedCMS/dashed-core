<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Dashed\DashedCore\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\SchemaOrg\Schema;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\View;
use Dashed\DashedCore\Models\Customsetting;

class FrontendMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Trailing slash redirect zo vroeg mogelijk, vóór we iets zwaars doen
        if (preg_match('/.+\/$/', $request->getRequestUri())) {
            $url = rtrim($request->getRequestUri(), '/');

            return redirect($url, 301);
        }

        $siteId = Sites::getActive();

        // ---- SETTINGS DIRECT, ZONDER EXTRA CACHE-LAAG ----

        $webmasterTags = [
            'google' => Customsetting::get('webmaster_tag_google', $siteId),
            'bing' => Customsetting::get('webmaster_tag_bing', $siteId),
            'alexa' => Customsetting::get('webmaster_tag_alexa', $siteId),
            'pinterest' => Customsetting::get('webmaster_tag_pinterest', $siteId),
            'yandex' => Customsetting::get('webmaster_tag_yandex', $siteId),
            'norton' => Customsetting::get('webmaster_tag_norton', $siteId),
        ];

        $siteName = Customsetting::get('site_name', $siteId, 'Website');
        $defaultMetaImageId = Customsetting::get('default_meta_data_image', $siteId, '');

        $logo = Customsetting::get('site_logo', $siteId, '');
        $favicon = Customsetting::get('site_favicon', $siteId, '');

        $companyStreet = Customsetting::get('company_street', $siteId);
        $companyStreetNumber = Customsetting::get('company_street_number', $siteId);
        $companyPostalCode = Customsetting::get('company_postal_code', $siteId);
        $companyCity = Customsetting::get('company_city', $siteId);
        $companyCountry = Customsetting::get('company_country', $siteId);

        $siteEmail = Customsetting::get('site_to_email', $siteId);
        $companyPhone = Customsetting::get('company_phone_number', $siteId);

        $googleMapsSynced = Customsetting::get('google_maps_reviews_synced', $siteId, false);
        $googleMapsRating = Customsetting::get('google_maps_rating', $siteId);
        $googleMapsReviewCount = Customsetting::get('google_maps_review_count', $siteId);

        // Tracking / marketing settings
        $googleTagmanagerId = Customsetting::get('google_tagmanager_id', $siteId);
        $triggerTikTokEvents = Customsetting::get('trigger_tiktok_events', $siteId, false);
        $facebookPixelConversionId = Customsetting::get('facebook_pixel_conversion_id', $siteId);
        $facebookPixelSiteId = Customsetting::get('facebook_pixel_site_id', $siteId);
        $triggerFacebookEvents = Customsetting::get('trigger_facebook_events', $siteId, false);
        $googleMerchantCenterId = Customsetting::get('google_merchant_center_id', $siteId);
        $enableGoogleMerchantReviewSurvey = Customsetting::get('enable_google_merchant_center_review_survey', $siteId, false);
        $enableGoogleMerchantReviewBadge = Customsetting::get('enable_google_merchant_center_review_badge', $siteId, false);
        $googleAnalyticsId = Customsetting::get('google_analytics_id', $siteId);

        $extraBodyScripts = Customsetting::get('extra_body_scripts', $siteId, '');
        $extraHeadScripts = Customsetting::get('extra_scripts', $siteId, '');

        $trackingSettings = [
            'google_tagmanager_id' => $googleTagmanagerId,
            'trigger_tiktok_events' => (bool)$triggerTikTokEvents,
            'facebook_pixel_conversion_id' => $facebookPixelConversionId,
            'facebook_pixel_site_id' => $facebookPixelSiteId,
            'trigger_facebook_events' => (bool)$triggerFacebookEvents,
            'google_merchant_center_id' => $googleMerchantCenterId,
            'enable_google_merchant_center_review_survey' => (bool)$enableGoogleMerchantReviewSurvey,
            'enable_google_merchant_center_review_badge' => (bool)$enableGoogleMerchantReviewBadge,
            'google_analytics_id' => $googleAnalyticsId,
        ];

        $company = [
            'street' => $companyStreet,
            'street_number' => $companyStreetNumber,
            'postal_code' => $companyPostalCode,
            'city' => $companyCity,
            'country' => $companyCountry,
            'email' => $siteEmail,
            'phone' => $companyPhone,
        ];

        $googleMaps = [
            'synced' => (bool)$googleMapsSynced,
            'rating' => $googleMapsRating,
            'review_count' => $googleMapsReviewCount,
        ];

        // ---- SEO META ----

        seo()->metaData('webmasterTags', $webmasterTags);
        seo()->metaData('robots', app()->isLocal() ? 'noindex, nofollow' : 'index, follow');
        seo()->metaData('metaTitle', $siteName);

        if (!seo()->metaData('metaImage') && $defaultMetaImageId) {
            $defaultMedia = mediaHelper()->getSingleMedia($defaultMetaImageId, 'original');
            seo()->metaData('metaImage', $defaultMedia->url ?? '');
        }

        // ---- ORGANIZATION SCHEMA ----

        $schema = Schema::organization()
            ->identifier($request->url() . '#Organization')
            ->legalName($siteName)
            ->email($company['email'])
            ->telephone($company['phone'])
            ->logo($logo->url ?? '')
            ->address(
                $company['street'] . ' ' . $company['street_number'] . ', ' .
                $company['postal_code'] . ' ' . $company['city'] . ', ' .
                $company['country']
            )
            ->addProperties([
                'address' => [
                    'streetAddress' => $company['street'] . ' ' . $company['street_number'],
                    'postalCode' => $company['postal_code'],
                    'addressCountry' => $company['country'],
                ],
            ])
            ->url($request->url())
            ->contactPoint(
                Schema::contactPoint()
                    ->telephone($company['phone'])
                    ->email($company['email'])
            );

        $reviewSchemas = Cache::remember(
            "schema:reviews:site:{$siteId}:v1",
            now()->addDay(),
            function () use ($siteId) {
                $reviews = Review::query()
                    ->whereNotNull('stars')
                    ->whereNotNull('review')
                    ->orderBy('stars', 'desc')
                    ->latest()
                    ->take(10)
                    ->get();

                if ($reviews->isEmpty()) {
                    return [];
                }

                $ratingMap = [
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5',
                ];

                return $reviews->map(function ($review) use ($ratingMap) {
                    return Schema::review()
                        ->author(
                            Schema::person()->name($review->name ?: 'Anoniem')
                        )
                        ->reviewBody((string)$review->review)
                        ->reviewRating(
                            Schema::rating()
                                ->ratingValue($ratingMap[(int)$review->stars] ?? (string)$review->stars)
                                ->bestRating('5')
                                ->worstRating('1')
                        );
                })->all();
            }
        );

        if (!empty($reviewSchemas)) {
            $schema->addProperties([
                'review' => $reviewSchemas,
            ]);

            foreach(Review::distinct('provider')->pluck('provider') as $provider) {
                $amountOfReviews = Review::query()
                    ->where('provider', $provider)
                    ->whereNotNull('stars')
                    ->whereNotNull('review')
                    ->count();
                $rating = Review::query()
                    ->where('provider', $provider)
                    ->whereNotNull('stars')
                    ->whereNotNull('review')
                    ->avg('stars');

                $schema->aggregateRating(
                    Schema::aggregateRating()
                        ->ratingValue((int)$rating)
                        ->bestRating(5)
                        ->worstRating(1)
                        ->reviewCount($amountOfReviews)
                        ->url($provider === 'own' ? url('/') : ($provider === 'google' ? 'https://www.google.com/' : ('https://www.' . $provider . '.com/'))),
                );
            }
        } else {
            if ($googleMaps['synced']) {
                $schema->aggregateRating(
                    Schema::aggregateRating()
                        ->ratingValue($googleMaps['rating'])
                        ->bestRating(5)
                        ->worstRating(1)
                        ->reviewCount($googleMaps['review_count'])
                        ->url('https://www.google.com/')
                );
            }
        }

        seo()->metaData('schemas', array_merge([
            'localBusiness' => $schema,
        ], seo()->metaData('schemas')));

        // ---- VIEW SHARES ----

        View::share('trackingSettings', $trackingSettings);
        View::share('extraBodyScripts', $extraBodyScripts);

        View::share('logo', $logo);
        View::share('favicon', $favicon);
        View::share('extraHeadScripts', $extraHeadScripts);
        View::share('siteName', $siteName);

        $response = $next($request);

        return $response;
    }

    protected function logMemory(string $label): void
    {
        if (!app()->environment('local')) {
            return;
        }

        logger()->info("MEM [FrontendMiddleware - {$label}]: " . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
    }
}

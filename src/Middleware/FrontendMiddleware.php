<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\SchemaOrg\Schema;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Review;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
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

        // ---- SITE-STATIC SHARED DATA (GECACHET PER SITE) ----
        //
        // Alle waarden hier zijn puur afgeleid van Customsettings en de
        // media-bibliotheek, ze hangen NIET af van de huidige URL, de
        // ingelogde gebruiker, of de locale. Ze zijn daarom veilig te cachen
        // per site.
        //
        // TTL: 5-minuten fallback TTL matching de spec vangnet, te vervangen
        // door event-driven purge in Fase 3's CacheInvalidator.
        //
        // Wat NIET in de cache zit (blijft live):
        //   - $schema: gebruikt $request->url() (request-dependent)
        //   - seo()->metaData('robots'): gebruikt app()->isLocal() (ok statisch, maar seo() is request-scoped)
        //   - $reviewSchemas: heeft eigen cache (schema:reviews:site:{siteId}:v1)
        $siteShared = Cache::remember(
            "frontend:site:{$siteId}:shared:v1",
            now()->addSeconds(300),
            function () use ($siteId) {
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
                $defaultMetaImageUrl = '';
                if ($defaultMetaImageId) {
                    $defaultMedia = mediaHelper()->getSingleMedia($defaultMetaImageId, 'original');
                    $defaultMetaImageUrl = $defaultMedia->url ?? '';
                }

                $logo = Customsetting::get('site_logo', $siteId, '');
                $favicon = Customsetting::get('site_favicon', $siteId, '');

                $company = [
                    'street' => Customsetting::get('company_street', $siteId),
                    'street_number' => Customsetting::get('company_street_number', $siteId),
                    'postal_code' => Customsetting::get('company_postal_code', $siteId),
                    'city' => Customsetting::get('company_city', $siteId),
                    'country' => Customsetting::get('company_country', $siteId),
                    'email' => Customsetting::get('site_to_email', $siteId),
                    'phone' => Customsetting::get('company_phone_number', $siteId),
                ];

                $googleMaps = [
                    'synced' => (bool) Customsetting::get('google_maps_reviews_synced', $siteId, false),
                    'rating' => Customsetting::get('google_maps_rating', $siteId),
                    'review_count' => Customsetting::get('google_maps_review_count', $siteId),
                ];

                $trackingSettings = [
                    'google_tagmanager_id' => Customsetting::get('google_tagmanager_id', $siteId),
                    'trigger_tiktok_events' => (bool) Customsetting::get('trigger_tiktok_events', $siteId, false),
                    'facebook_pixel_conversion_id' => Customsetting::get('facebook_pixel_conversion_id', $siteId),
                    'facebook_pixel_site_id' => Customsetting::get('facebook_pixel_site_id', $siteId),
                    'trigger_facebook_events' => (bool) Customsetting::get('trigger_facebook_events', $siteId, false),
                    'google_merchant_center_id' => Customsetting::get('google_merchant_center_id', $siteId),
                    'enable_google_merchant_center_review_survey' => (bool) Customsetting::get('enable_google_merchant_center_review_survey', $siteId, false),
                    'enable_google_merchant_center_review_badge' => (bool) Customsetting::get('enable_google_merchant_center_review_badge', $siteId, false),
                    'google_analytics_id' => Customsetting::get('google_analytics_id', $siteId),
                ];

                return [
                    'siteName' => $siteName,
                    'logo' => $logo,
                    'favicon' => $favicon,
                    'company' => $company,
                    'googleMaps' => $googleMaps,
                    'webmasterTags' => $webmasterTags,
                    'trackingSettings' => $trackingSettings,
                    'extraBodyScripts' => Customsetting::get('extra_body_scripts', $siteId, ''),
                    'extraHeadScripts' => Customsetting::get('extra_scripts', $siteId, ''),
                    'defaultMetaImageUrl' => $defaultMetaImageUrl,
                ];
            }
        );

        // Destructure cached struct into local variables (same names as before).
        $siteName = $siteShared['siteName'];
        $logo = $siteShared['logo'];
        $favicon = $siteShared['favicon'];
        $company = $siteShared['company'];
        $googleMaps = $siteShared['googleMaps'];
        $webmasterTags = $siteShared['webmasterTags'];
        $trackingSettings = $siteShared['trackingSettings'];
        $extraBodyScripts = $siteShared['extraBodyScripts'];
        $extraHeadScripts = $siteShared['extraHeadScripts'];

        // ---- SEO META ----

        seo()->metaData('webmasterTags', $webmasterTags);
        seo()->metaData('robots', app()->isLocal() ? 'noindex, nofollow' : 'index, follow');
        seo()->metaData('metaTitle', $siteName);

        if (! seo()->metaData('metaImage') && $siteShared['defaultMetaImageUrl']) {
            seo()->metaData('metaImage', $siteShared['defaultMetaImageUrl']);
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
                    $reviewText = (string) $review->review;
                    $reviewName = mb_strlen($reviewText) > 60
                        ? mb_substr($reviewText, 0, 57) . '...'
                        : $reviewText;

                    return Schema::review()
                        ->name($reviewName ?: 'Review')
                        ->author(
                            Schema::person()->name($review->name ?: 'Anoniem')
                        )
                        ->reviewBody($reviewText)
                        ->reviewRating(
                            Schema::rating()
                                ->ratingValue($ratingMap[(int)$review->stars] ?? (string)$review->stars)
                                ->bestRating('5')
                                ->worstRating('1')
                        );
                })->all();
            }
        );

        if (! empty($reviewSchemas)) {
            $schema->addProperties([
                'review' => $reviewSchemas,
            ]);

            foreach (Review::distinct('provider')->pluck('provider') as $provider) {
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
        if (! app()->environment('local')) {
            return;
        }

        logger()->info("MEM [FrontendMiddleware - {$label}]: " . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
    }
}

<?php

namespace Dashed\DashedCore\Classes\Seo;

use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Review;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

/**
 * Het bedrijfsprofiel van een site, als plat array voor SiteSchemas.
 *
 * Alles komt uit wat de site al weet (kaart Algemeen, SEO-kaart, Google
 * Maps, de haken van andere pakketten); de kaart Bedrijfsprofiel bevat
 * alleen afwijkingen en gaat voor. Pakketten melden zich aan met
 * cms()->builder('organizationSameAs' | 'organizationOpeningHours' |
 * 'siteSearchUrl', [fn (string $siteId) => ...]) en
 * cms()->builder('organizationProfileSettings', ['hun_sleutel']) voor de
 * instellingen die het profiel moeten verversen.
 */
class BusinessProfile
{
    public const TYPES = [
        'Organization',
        'OnlineStore',
        'LocalBusiness',
        'Store',
        'Restaurant',
        'ProfessionalService',
        'MedicalBusiness',
        'HomeAndConstructionBusiness',
    ];

    /** Subtypes van LocalBusiness: een plaats met openingstijden, prijsklasse en geo. */
    public const LOCAL_TYPES = [
        'LocalBusiness',
        'Store',
        'Restaurant',
        'ProfessionalService',
        'MedicalBusiness',
        'HomeAndConstructionBusiness',
    ];

    public const WATCHED_SETTINGS = [
        'site_name',
        'site_url',
        'site_logo',
        'site_to_email',
        'company_phone_number',
        'company_street',
        'company_street_number',
        'company_postal_code',
        'company_city',
        'company_country',
        'google_maps_places_id',
        'google_maps_rating',
        'google_maps_review_count',
        'google_maps_reviews_synced',
        'default_meta_data_twitter_site',
    ];

    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public static function for(string $siteId, string $locale): array
    {
        return Cache::rememberForever(static::cacheKey($siteId, $locale), fn () => static::build($siteId, $locale, withOverrides: true));
    }

    public static function cacheKey(string $siteId, string $locale): string
    {
        return "seo:business-profile:{$siteId}:{$locale}:v1";
    }

    /**
     * Vergeet het profiel van elke site en taal en leegt de response-cache,
     * want het schema staat in de HTML van elke pagina. Geen eigen
     * ontdubbeling: de origin-flush per tag is goedkoop en de edge-purge in
     * CacheInvalidator is al gethrottled met een naloper. Een vlag die "al
     * geflusht" onthoudt zou in een wachtrij-worker, die het profiel nooit
     * zelf opbouwt, na de eerste review nooit meer flushen.
     */
    public static function forget(): void
    {
        foreach (Sites::getSites() as $site) {
            foreach (array_keys(Locales::getLocalesArray()) as $locale) {
                Cache::forget(static::cacheKey($site['id'], (string) $locale));
            }

            CacheInvalidator::flushSite($site['id']);
        }
    }

    public static function watches(string $setting): bool
    {
        return str_starts_with($setting, 'business_')
            || in_array($setting, self::WATCHED_SETTINGS, true)
            || in_array($setting, cms()->builder('organizationProfileSettings'), true);
    }

    public static function automatic(string $siteId, string $locale): array
    {
        return static::build($siteId, $locale, withOverrides: false);
    }

    protected static function build(string $siteId, string $locale, bool $withOverrides): array
    {
        $get = fn (string $name) => $withOverrides ? Customsetting::get($name, $siteId) : null;
        $url = Sites::url('/', $siteId);

        $type = $get('business_type');
        if (! in_array($type, self::TYPES, true)) {
            $type = class_exists(\Dashed\DashedEcommerceCore\Models\Product::class) ? 'OnlineStore' : 'Organization';
        }

        return [
            'type' => $type,
            'id' => $url . '#organization',
            'name' => (string) Customsetting::get('site_name', $siteId),
            'url' => $url,
            'logo' => static::logo($siteId),
            'telephone' => static::stringOrNull(Customsetting::get('company_phone_number', $siteId)),
            'email' => static::stringOrNull(Customsetting::get('site_to_email', $siteId)),
            'address' => static::address($siteId),
            'sameAs' => static::sameAs($siteId, $withOverrides ? static::jsonList($get('business_same_as')) : []),
            'openingHours' => static::openingHours($siteId, $withOverrides ? static::jsonList($get('business_opening_hours')) : []),
            'priceRange' => static::stringOrNull($get('business_price_range')),
            'geo' => static::geo($get('business_geo_lat'), $get('business_geo_lng')),
            'rating' => static::rating($siteId),
            'reviews' => static::reviews(),
            'searchUrl' => static::firstFromHook('siteSearchUrl', $siteId),
            'locale' => $locale,
        ];
    }

    protected static function logo(string $siteId): ?array
    {
        $media = mediaHelper()->getSingleMedia(Customsetting::get('site_logo', $siteId), 'huge');

        if (! is_object($media) || empty($media->url)) {
            return null;
        }

        return [
            'url' => (string) $media->url,
            'width' => isset($media->width) ? (int) $media->width : null,
            'height' => isset($media->height) ? (int) $media->height : null,
        ];
    }

    protected static function address(string $siteId): ?array
    {
        $streetName = static::stringOrNull(Customsetting::get('company_street', $siteId));
        $city = static::stringOrNull(Customsetting::get('company_city', $siteId));

        // Een huisnummer zonder straat is geen adres.
        if (! $streetName && ! $city) {
            return null;
        }

        $street = trim($streetName . ' ' . (string) Customsetting::get('company_street_number', $siteId));

        return array_filter([
            'streetAddress' => $street ?: null,
            'postalCode' => static::stringOrNull(Customsetting::get('company_postal_code', $siteId)),
            'addressLocality' => $city,
            'addressCountry' => static::stringOrNull(Customsetting::get('company_country', $siteId)),
        ]);
    }

    /**
     * @param  array<int, array{url?: string}|string>  $extra  regels van de kaart
     * @return array<int, string>
     */
    protected static function sameAs(string $siteId, array $extra): array
    {
        $urls = [];

        if ($handle = static::stringOrNull(Customsetting::get('default_meta_data_twitter_site', $siteId))) {
            $urls[] = str_starts_with($handle, 'https://') ? $handle : 'https://x.com/' . ltrim($handle, '@');
        }

        if ($placeId = static::stringOrNull(Customsetting::get('google_maps_places_id', $siteId))) {
            $urls[] = 'https://www.google.com/maps/place/?q=place_id:' . $placeId;
        }

        foreach (cms()->builder('organizationSameAs') as $hook) {
            $urls = [...$urls, ...(array) $hook($siteId)];
        }

        foreach ($extra as $row) {
            $urls[] = is_array($row) ? ($row['url'] ?? null) : $row;
        }

        return array_values(array_unique(array_filter($urls, fn ($url) => is_string($url) && str_starts_with($url, 'https://'))));
    }

    /**
     * @param  array<int, array{days: array<int, string>, opens: string, closes: string}>  $override
     * @return array<int, array{days: array<int, string>, opens: string, closes: string}>
     */
    protected static function openingHours(string $siteId, array $override): array
    {
        $rules = $override;

        if (! $rules) {
            foreach (cms()->builder('organizationOpeningHours') as $hook) {
                $rules = (array) $hook($siteId);

                if ($rules) {
                    break;
                }
            }
        }

        // Gelijke tijden samenvoegen tot één regel, dagen in weekvolgorde.
        $byTime = [];
        foreach ($rules as $rule) {
            if (empty($rule['days']) || empty($rule['opens']) || empty($rule['closes'])) {
                continue;
            }

            $key = $rule['opens'] . '-' . $rule['closes'];
            $byTime[$key] ??= ['days' => [], 'opens' => $rule['opens'], 'closes' => $rule['closes']];
            $byTime[$key]['days'] = [...$byTime[$key]['days'], ...(array) $rule['days']];
        }

        return array_values(array_map(function (array $rule) {
            $days = array_values(array_filter(self::DAYS, fn ($day) => in_array($day, $rule['days'], true)));

            return ['days' => $days, 'opens' => $rule['opens'], 'closes' => $rule['closes']];
        }, $byTime));
    }

    protected static function geo($lat, $lng): ?array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return ['latitude' => (float) $lat, 'longitude' => (float) $lng];
    }

    protected static function rating(string $siteId): ?array
    {
        $query = Review::query()->whereNotNull('stars')->whereNotNull('review');
        $count = (int) $query->count();

        if ($count > 0) {
            return ['value' => round((float) $query->avg('stars'), 1), 'count' => $count];
        }

        if (Customsetting::get('google_maps_reviews_synced', $siteId) && is_numeric(Customsetting::get('google_maps_rating', $siteId))) {
            return [
                'value' => round((float) Customsetting::get('google_maps_rating', $siteId), 1),
                'count' => (int) Customsetting::get('google_maps_review_count', $siteId),
            ];
        }

        return null;
    }

    /** @return array<int, array{name: string, body: string, stars: int}> */
    protected static function reviews(): array
    {
        return Review::query()
            ->whereNotNull('stars')
            ->whereNotNull('review')
            ->orderByDesc('stars')
            ->latest()
            ->orderByDesc('id')
            ->take(10)
            ->get()
            ->map(fn (Review $review) => [
                'name' => (string) ($review->name ?: 'Anoniem'),
                'body' => (string) $review->review,
                'stars' => (int) $review->stars,
            ])
            ->all();
    }

    protected static function firstFromHook(string $name, string $siteId): ?string
    {
        foreach (cms()->builder($name) as $hook) {
            $value = $hook($siteId);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected static function jsonList($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values($value) : [];
    }

    protected static function stringOrNull($value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}

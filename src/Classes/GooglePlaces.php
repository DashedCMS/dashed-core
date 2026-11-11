<?php

namespace Dashed\DashedCore\Classes;

use Throwable;
use Illuminate\Support\Facades\Http;

/**
 * Leest cijfer en aantal reviews van een bedrijf uit Google Places.
 *
 * Google heeft de oude Places API ("Places API", maps.googleapis.com) vervangen
 * door "Places API (New)" (places.googleapis.com); een nieuwe sleutel werkt
 * alleen nog op de nieuwe. We vragen daarom eerst de nieuwe API en vallen terug
 * op de oude, zodat een project met een oude sleutel blijft werken tot die is
 * vervangen. `api` zegt welke van de twee antwoordde.
 */
class GooglePlaces
{
    public const API_NEW = 'new';

    public const API_LEGACY = 'legacy';

    public const SETTING_API = 'google_maps_places_api';

    public const SETTING_ERROR = 'google_maps_places_error';

    public const CONSOLE_LIBRARY_URL = 'https://console.cloud.google.com/apis/library/places.googleapis.com';

    public const CONSOLE_CREDENTIALS_URL = 'https://console.cloud.google.com/apis/credentials';

    public const CONSOLE_BILLING_URL = 'https://console.cloud.google.com/billing';

    public const PLACE_ID_FINDER_URL = 'https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder';

    public const DOCS_URL = 'https://developers.google.com/maps/documentation/places/web-service/get-api-key';

    /**
     * @return array{ok: bool, api: ?string, rating: ?float, count: ?int, name: ?string, error: ?string, new_error: ?string}
     */
    public static function rating(?string $key, ?string $placeId): array
    {
        $key = trim((string) $key);
        $placeId = trim((string) $placeId);

        if ($key === '' || $placeId === '') {
            return self::result(false, error: $key === '' ? 'Er is geen Google Maps Places key ingevuld.' : 'Er is geen Google Maps Places ID ingevuld.');
        }

        $new = self::fromNewApi($key, $placeId);
        if ($new['ok']) {
            return $new;
        }

        $legacy = self::fromLegacyApi($key, $placeId);
        if ($legacy['ok']) {
            $legacy['new_error'] = $new['error'];

            return $legacy;
        }

        // De fout van de nieuwe API is de fout die opgelost moet worden.
        $new['new_error'] = $new['error'];

        return $new;
    }

    /** @return array{ok: bool, api: ?string, rating: ?float, count: ?int, name: ?string, error: ?string, new_error: ?string} */
    public static function fromNewApi(string $key, string $placeId): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Goog-Api-Key' => $key,
                    'X-Goog-FieldMask' => 'displayName,rating,userRatingCount',
                ])
                ->get('https://places.googleapis.com/v1/places/' . rawurlencode($placeId));
        } catch (Throwable $e) {
            return self::result(false, error: 'Google Places is niet bereikbaar: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            return self::result(false, error: self::shorten((string) ($response->json('error.message') ?: 'HTTP ' . $response->status())));
        }

        return self::result(
            true,
            self::API_NEW,
            (float) ($response->json('rating') ?? 0),
            (int) ($response->json('userRatingCount') ?? 0),
            $response->json('displayName.text'),
        );
    }

    /** @return array{ok: bool, api: ?string, rating: ?float, count: ?int, name: ?string, error: ?string, new_error: ?string} */
    public static function fromLegacyApi(string $key, string $placeId): array
    {
        try {
            $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'key' => $key,
                'fields' => 'name,rating,user_ratings_total',
            ]);
        } catch (Throwable $e) {
            return self::result(false, error: 'Google Places is niet bereikbaar: ' . $e->getMessage());
        }

        if (! $response->ok() || $response->json('status') !== 'OK') {
            return self::result(false, error: self::shorten((string) ($response->json('error_message') ?: $response->json('status') ?: 'HTTP ' . $response->status())));
        }

        return self::result(
            true,
            self::API_LEGACY,
            (float) ($response->json('result.rating') ?? 0),
            (int) ($response->json('result.user_ratings_total') ?? 0),
            $response->json('result.name'),
        );
    }

    /** @return array{ok: bool, api: ?string, rating: ?float, count: ?int, name: ?string, error: ?string, new_error: ?string} */
    protected static function result(bool $ok, ?string $api = null, ?float $rating = null, ?int $count = null, ?string $name = null, ?string $error = null): array
    {
        return ['ok' => $ok, 'api' => $api, 'rating' => $rating, 'count' => $count, 'name' => $name, 'error' => $error, 'new_error' => null];
    }

    protected static function shorten(string $message): string
    {
        return mb_substr(trim(preg_replace('/\s+/', ' ', $message)), 0, 400);
    }
}

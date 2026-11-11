<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Classes\GooglePlaces;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncGoogleReviews implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900]; // 1m, 5m, 15m

    public int $timeout = 60; // job timeout (seconds)

    public function handle(): void
    {
        $key = (string) Customsetting::get('google_maps_places_key');
        $placeId = (string) Customsetting::get('google_maps_places_id');

        if (! $key || ! $placeId) {
            // netjes: als config mist, markeer als niet gesynced
            $this->markFailed(null);

            return;
        }

        $result = GooglePlaces::rating($key, $placeId);

        if (! $result['ok']) {
            $this->markFailed($result['error']);
            Log::warning('SyncGoogleReviews: Google Places gaf geen cijfer', ['error' => $result['error']]);

            return;
        }

        Customsetting::set('google_maps_rating', $result['rating'] ?? 0);
        Customsetting::set('google_maps_review_count', $result['count'] ?? 0);
        Customsetting::set('google_maps_reviews_synced', 1);
        Customsetting::set(GooglePlaces::SETTING_API, $result['api']);
        // Werkt alleen de oude API nog, dan is dit de reden dat de nieuwe weigerde.
        Customsetting::set(GooglePlaces::SETTING_ERROR, $result['api'] === GooglePlaces::API_LEGACY ? $result['new_error'] : null);
    }

    protected function markFailed(?string $error = null): void
    {
        Customsetting::set('google_maps_rating', null);
        Customsetting::set('google_maps_review_count', null);
        Customsetting::set('google_maps_reviews_synced', 0);
        Customsetting::set(GooglePlaces::SETTING_API, null);
        Customsetting::set(GooglePlaces::SETTING_ERROR, $error);
    }
}

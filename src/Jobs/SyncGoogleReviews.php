<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
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
            Customsetting::set('google_maps_rating', null);
            Customsetting::set('google_maps_review_count', null);
            Customsetting::set('google_maps_reviews_synced', 0);

            return;
        }

        $url = 'https://maps.googleapis.com/maps/api/place/details/json';

        try {
            $response = Http::timeout(15)
                ->retry(2, 500) // 2 retries, 0.5s delay
                ->get($url, [
                    'place_id' => $placeId,
                    'key' => $key,
                    'fields' => 'rating,user_ratings_total,reviews',
                ]);

            if (! $response->ok()) {
                $this->markFailed();
                Log::warning('SyncGoogleReviews: non-200 response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return;
            }

            $json = $response->json();

            $status = $json['status'] ?? null;

            dd($json);
            if ($status !== 'OK') {
                // Google statuses: ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED, INVALID_REQUEST, UNKNOWN_ERROR
                $this->markFailed();
                Log::warning('SyncGoogleReviews: Google API status not OK', [
                    'status' => $status,
                    'error_message' => $json['error_message'] ?? null,
                ]);

                return;
            }

            $result = $json['result'] ?? [];

            Customsetting::set('google_maps_rating', $result['rating'] ?? 0);
            Customsetting::set('google_maps_review_count', $result['user_ratings_total'] ?? 0);
            Customsetting::set('google_maps_reviews_synced', 1);
        } catch (\Throwable $e) {
            $this->markFailed();
            Log::error('SyncGoogleReviews: exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
        }
        dd('asdf');
    }

    protected function markFailed(): void
    {
        Customsetting::set('google_maps_rating', null);
        Customsetting::set('google_maps_review_count', null);
        Customsetting::set('google_maps_reviews_synced', 0);
    }
}

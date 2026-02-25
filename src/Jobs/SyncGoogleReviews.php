<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
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

    public $tries = 1;

    public $timeout = 60 * 60 * 3;

    public function handle(): void
    {
        if (Customsetting::get('google_maps_places_key') && Customsetting::get('google_maps_places_id')) {
            $url = $this->buildUrl('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => Customsetting::get('google_maps_places_id'),
                'key' => Customsetting::get('google_maps_places_key'),
                'fields' => 'rating,user_ratings_total',
            ]);

            $reviews = Http::get($url)->json();
            if ($reviews['status'] === 'OK') {
                $reviews = $reviews['result'];
                Customsetting::set('google_maps_rating', $reviews['rating'] ?? 0);
                Customsetting::set('google_maps_review_count', $reviews['user_ratings_total'] ?? 0);
                Customsetting::set('google_maps_reviews_synced', 1);
                $this->info('Google Reviews Synced');
            } else {
                $this->error('Google Reviews Sync Failed');
                Customsetting::set('google_maps_rating', null);
                Customsetting::set('google_maps_review_count', null);
                Customsetting::set('google_maps_reviews_synced', 0);
            }
        }
    }
}

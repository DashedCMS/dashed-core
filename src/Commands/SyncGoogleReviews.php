<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Helpers\Http\UrlBuilderTrait;

class SyncGoogleReviews extends Command
{
    use UrlBuilderTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:sync-google-reviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Reviews';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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

<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Models\Review;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Classes\GoogleBusinessClient;

class SyncGoogleBusinessReviews implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public int $timeout = 120;

    public function handle(GoogleBusinessClient $google): void
    {
        $client = $google->makeClient();
        $accessToken = $client->getAccessToken()['access_token'] ?? null;

        if (! $accessToken) {
            throw new \RuntimeException('Geen access_token gekregen.');
        }

        // Hier kan je OF hard 1 location syncen, OF alles doorlopen.
        // Voor start: 1 location is easiest.
        $locationName = (string) Customsetting::get('google_business_location_name'); // "accounts/123/locations/456"
        if (! $locationName) {
            throw new \RuntimeException('Customsetting google_business_location_name ontbreekt.');
        }

        $url = "https://mybusiness.googleapis.com/v4/{$locationName}/reviews";
        $pageToken = null;

        do {
            $res = Http::withToken($accessToken)
                ->timeout(25)
                ->retry(3, 800)
                ->get($url, array_filter([
                    'pageSize' => 200,
                    'pageToken' => $pageToken,
                    'orderBy' => 'updateTime desc',
                ]));

            if (! $res->ok()) {
                Log::warning('Google Reviews sync failed', ['status' => $res->status(), 'body' => $res->body()]);

                throw new \RuntimeException('Google reviews list failed: ' . $res->status());
            }

            foreach (($res->json('reviews') ?? []) as $review) {
                $this->upsertIntoDashedReviews($review);
            }

            $pageToken = $res->json('nextPageToken');
        } while ($pageToken);
    }

    protected function upsertIntoDashedReviews(array $review): void
    {
        $name = $review['name'] ?? null; // ".../reviews/{id}"
        if (! $name) {
            return;
        }

        $reviewId = last(explode('/reviews/', $name));

        $ratingMap = ['ONE' => 1,'TWO' => 2,'THREE' => 3,'FOUR' => 4,'FIVE' => 5];
        $stars = $ratingMap[$review['starRating'] ?? ''] ?? null;

        Review::updateOrCreate(
            [
                'provider' => 'google',
                'review_id' => $reviewId,
            ],
            [
                'name' => $review['reviewer']['displayName'] ?? null,
                'company' => 'Google',
                'profile_image' => $review['reviewer']['profilePhotoUrl'] ?? null,
                'review' => $review['comment'] ?? null,
                'stars' => $stars,
                'image' => null, // google review heeft meestal geen “image” field zoals jij ‘m bedoelt
            ]
        );
    }
}

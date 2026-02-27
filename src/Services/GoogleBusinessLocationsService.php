<?php

namespace Dashed\DashedCore\Support;

use Dashed\DashedCore\Classes\GoogleBusinessClient;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleBusinessLocationsService
{
    public function getLocationOptions(): array
    {
        $siteId = Sites::getActive();

        return Cache::remember("google:business:locations:site:{$siteId}:v1", now()->addMinutes(10), function () use ($siteId) {
            $refreshToken = (string) Customsetting::get('google_oauth_refresh_token', $siteId);

            if (! $refreshToken) {
                return [];
            }

            /** @var GoogleBusinessClient $google */
            $google = app(GoogleBusinessClient::class);
            $accessToken = $google->getAccessToken();

            // 1) Accounts
            $accountsRes = Http::withToken($accessToken)
                ->timeout(20)
                ->retry(2, 500)
                ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

            if (! $accountsRes->ok()) {
                throw new \RuntimeException('Accounts ophalen faalde: ' . $accountsRes->status());
            }

            $accounts = $accountsRes->json('accounts') ?? [];

            $locations = [];

            // 2) Locations per account (met pagination)
            foreach ($accounts as $account) {
                $accountName = $account['name'] ?? null; // accounts/123
                if (! $accountName) {
                    continue;
                }

                $pageToken = null;

                do {
                    $locRes = Http::withToken($accessToken)
                        ->timeout(20)
                        ->retry(2, 500)
                        ->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations", array_filter([
                            'pageSize' => 100,
                            'pageToken' => $pageToken,
                        ]));

                    if (! $locRes->ok()) {
                        throw new \RuntimeException("Locaties ophalen faalde ({$accountName}): " . $locRes->status());
                    }

                    $locations = array_merge($locations, $locRes->json('locations') ?? []);
                    $pageToken = $locRes->json('nextPageToken');
                } while ($pageToken);
            }

            // Dropdown options: [locationName => label]
            $options = [];

            foreach ($locations as $location) {
                $name = $location['name'] ?? null; // accounts/.../locations/...
                if (! $name) {
                    continue;
                }

                $title = $location['title'] ?? 'Onbekende locatie';

                $addr = $location['storefrontAddress'] ?? [];
                $street = trim(($addr['addressLines'][0] ?? '') . ' ' . ($addr['addressLines'][1] ?? ''));
                $postal = $addr['postalCode'] ?? '';
                $city = $addr['locality'] ?? '';
                $country = $addr['regionCode'] ?? '';

                $pretty = trim(implode(', ', array_filter([
                    $title,
                    trim($street),
                    trim("{$postal} {$city}"),
                    $country,
                ])));

                $options[$name] = $pretty;
            }

            asort($options);

            return $options;
        });
    }

    public function clearCache(): void
    {
        $siteId = Sites::getActive();
        Cache::forget("google:business:locations:site:{$siteId}:v1");
    }
}

<?php

namespace Dashed\DashedCore\Classes\Caching;

use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Classes\Sites;

class CloudflarePurger
{
    public static function purgeZone(?string $siteId = null): void
    {
        $siteId = $siteId ?? Sites::getActive();
        $cfg = CloudflareConfig::for($siteId);

        if (! $cfg->configured()) {
            Log::debug('CloudflarePurger: skipping purge, no credentials configured.', ['site_id' => $siteId]);

            return;
        }

        try {
            $response = Http::withToken($cfg->apiToken())
                ->post("https://api.cloudflare.com/client/v4/zones/{$cfg->zoneId()}/purge_cache", [
                    'purge_everything' => true,
                ]);

            $body = $response->json();

            if (! $response->successful() || ($body['success'] ?? false) !== true) {
                Log::warning('CloudflarePurger: purge request did not succeed.', [
                    'site_id' => $siteId,
                    'zone_id' => $cfg->zoneId(),
                    'status' => $response->status(),
                    'body' => $body,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('CloudflarePurger: exception during purge request.', [
                'site_id' => $siteId,
                'zone_id' => $cfg->zoneId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}

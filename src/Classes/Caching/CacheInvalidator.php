<?php

namespace Dashed\DashedCore\Classes\Caching;

use Dashed\DashedCore\Classes\CacheProfile;
use Dashed\DashedCore\Classes\FragmentCache;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Jobs\PurgeCloudflareJob;
use Illuminate\Support\Facades\Cache;

class CacheInvalidator
{
    /**
     * Purge all response-cache entries for a single site.
     *
     * Grofmazig (v1): flusht alles getagd met response:site:{siteId}.
     * Per-URL granularity is a later optimization.
     */
    public static function flushSite(?string $siteId = null): void
    {
        $siteId = $siteId ?? Sites::getActive();

        FragmentCache::flushTag('response:site:' . $siteId);

        if (CacheProfile::forSite($siteId)->edgeEnabled() && CloudflareConfig::for($siteId)->configured()) {
            PurgeCloudflareJob::dispatch($siteId);
        }
    }

    /**
     * Purge the FrontendMiddleware shared-settings cache for a site.
     *
     * Closes the Fase-1 staleness gap: when Customsetting changes, the
     * shared block (logo, site name, webmaster tags, …) is immediately stale.
     */
    public static function flushFrontendShared(?string $siteId = null): void
    {
        $siteId = $siteId ?? Sites::getActive();

        Cache::forget('frontend:site:' . $siteId . ':shared:v1');
    }

    /**
     * Purge ALL response-cache entries across every site.
     *
     * Use sparingly - e.g. global template change or full rebuild.
     */
    public static function flushAll(): void
    {
        // NB: this purges the origin Redis response-cache only, NOT the Cloudflare edge.
        // If you wire a global-purge action to flushAll(), also purge Cloudflare per
        // edge-enabled site (dispatch PurgeCloudflareJob), or the edge stays stale.
        FragmentCache::flushTag('response');
    }

    /**
     * Purge the response-cache for the site that owns the given model.
     *
     * v1: grofmazig - purge the whole site. The model's site_ids/site_id is
     * read when available; otherwise falls back to the active site.
     */
    public static function forModel(mixed $model): void
    {
        $siteId = null;

        // Try to read a model-level site identifier (common dashed conventions).
        if (isset($model->site_id) && $model->site_id) {
            $siteId = (string) $model->site_id;
        } elseif (isset($model->site_ids) && is_array($model->site_ids) && count($model->site_ids)) {
            // Multi-site model: flush all sites it belongs to.
            foreach ($model->site_ids as $id) {
                static::flushSite((string) $id);
            }

            return;
        }

        static::flushSite($siteId);
    }
}

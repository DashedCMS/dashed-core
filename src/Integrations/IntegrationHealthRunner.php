<?php

namespace Dashed\DashedCore\Integrations;

use Throwable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Enums\IntegrationStatus;

/**
 * Probes integration health checks with a 5-minute cache so the dashboard
 * stays responsive even when an upstream API is slow. Each successful
 * probe also persists a heartbeat to Customsetting so the dashboard can
 * show "last successful contact X ago" even when the most recent probe
 * failed.
 */
class IntegrationHealthRunner
{
    public const CACHE_TTL_SECONDS = 300;

    public function run(IntegrationDefinition $def, ?string $siteId = null): IntegrationHealth
    {
        $cacheKey = $this->cacheKey($def->slug, $siteId);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($def, $siteId) {
            return $this->runUncached($def, $siteId);
        });
    }

    public function forget(string $slug, ?string $siteId = null): void
    {
        Cache::forget($this->cacheKey($slug, $siteId));
    }

    protected function runUncached(IntegrationDefinition $def, ?string $siteId): IntegrationHealth
    {
        try {
            $result = $def->runHealthCheck($siteId);
        } catch (Throwable $e) {
            $result = IntegrationHealth::failing($e->getMessage());
        }

        $this->persistHeartbeat($def->slug, $siteId, $result);

        if ($result->status === IntegrationStatus::Failing) {
            IntegrationEvents::failed($def->slug, $result->message, $siteId);
        }

        // Een health check die zelf geen moment van laatste contact meegeeft
        // krijgt het jongste van de opgeslagen hartslag en de laatste gemelde
        // gelukte synchronisatie; anders staat er "Nog niet getest" bij een
        // koppeling die elke dag werkt.
        if ($result->lastSuccessAt === null) {
            $result = $result->withLastSuccess($this->lastSuccess($def->slug, $siteId));
        }

        return $result;
    }

    protected function persistHeartbeat(string $slug, ?string $siteId, IntegrationHealth $health): void
    {
        try {
            if ($health->status === IntegrationStatus::Connected) {
                Customsetting::set("integration_{$slug}_last_success_at", Carbon::now()->toIso8601String(), $siteId);
                Customsetting::set("integration_{$slug}_last_error", '', $siteId);
            } elseif ($health->message !== null) {
                Customsetting::set("integration_{$slug}_last_error", $health->message, $siteId);
            }
        } catch (Throwable) {
            // Heartbeat persistence is best-effort; failure here must not
            // sink the health-check result.
        }
    }

    protected function lastSuccess(string $slug, ?string $siteId): ?Carbon
    {
        $heartbeat = rescue(fn () => Customsetting::get("integration_{$slug}_last_success_at", $siteId), null, false);
        $heartbeat = $heartbeat ? rescue(fn () => Carbon::parse($heartbeat), null, false) : null;
        $event = IntegrationEvents::lastSuccess($slug, $siteId);

        // Een gemelde synchronisatie zegt meer dan de hartslag van een health
        // check die vaak alleen kijkt of de instellingen gevuld zijn.
        return $event ?? $heartbeat;
    }

    protected function cacheKey(string $slug, ?string $siteId): string
    {
        return 'dashed:integration_health:' . $slug . ':' . ($siteId ?? '_');
    }
}

<?php

namespace Dashed\DashedCore\Integrations;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Dashed\DashedCore\Models\IntegrationEvent;

/**
 * Wat een koppeling zelf meldt over zijn synchronisaties: een gelukte of
 * mislukte ronde, met een korte melding. De health check zegt of de koppeling
 * nu bereikbaar is; dit zegt of het werk er de afgelopen tijd echt doorheen
 * kwam. Een pakket meldt zich met `IntegrationEvents::ok('bol')` en
 * `IntegrationEvents::failed('bol', $melding)`, achter een class_exists, want
 * een oudere dashed-core kent deze klasse niet.
 *
 * Loggen mag een synchronisatie nooit tegenhouden: alles loopt door rescue.
 */
class IntegrationEvents
{
    public const ERROR_WINDOW_HOURS = 24;

    public static function ok(string $slug, ?string $message = null, ?string $siteId = null): void
    {
        self::record($slug, true, $message, $siteId);
    }

    public static function failed(string $slug, ?string $message = null, ?string $siteId = null): void
    {
        self::record($slug, false, $message, $siteId);
    }

    public static function record(string $slug, bool $ok, ?string $message = null, ?string $siteId = null): void
    {
        rescue(fn () => IntegrationEvent::query()->create([
            'slug' => $slug,
            'site_id' => $siteId,
            'ok' => $ok,
            'message' => $message === null ? null : Str::limit($message, 1000),
            'created_at' => now(),
        ]), report: false);
    }

    public static function failuresSince(string $slug, ?string $siteId = null, ?Carbon $since = null): int
    {
        try {
            return IntegrationEvent::query()
                ->where('slug', $slug)
                ->where('ok', false)
                ->where('created_at', '>=', $since ?? now()->subHours(self::ERROR_WINDOW_HOURS))
                ->when($siteId !== null, fn ($query) => $query->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $siteId)))
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    public static function lastSuccess(string $slug, ?string $siteId = null): ?Carbon
    {
        return self::latest($slug, true, $siteId)?->created_at;
    }

    public static function lastFailure(string $slug, ?string $siteId = null): ?IntegrationEvent
    {
        return self::latest($slug, false, $siteId);
    }

    protected static function latest(string $slug, bool $ok, ?string $siteId): ?IntegrationEvent
    {
        try {
            return IntegrationEvent::query()
                ->where('slug', $slug)
                ->where('ok', $ok)
                ->when($siteId !== null, fn ($query) => $query->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $siteId)))
                ->latest('id')
                ->first();
        } catch (Throwable) {
            return null;
        }
    }
}

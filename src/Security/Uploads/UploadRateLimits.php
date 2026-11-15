<?php

namespace Dashed\DashedCore\Security\Uploads;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Benoemde rate limiter 'dashed-livewire-upload' voor /livewire/upload-file,
 * per IP én per gebruiker. Paneelgebruikers krijgen een ruimer budget per
 * account (meerdere afbeeldingen tegelijk in de mediabibliotheek).
 * Componenten met eigen uploads kunnen hem via attempt() ook gebruiken.
 */
class UploadRateLimits
{
    public const LIVEWIRE = 'dashed-livewire-upload';

    public static function register(): void
    {
        RateLimiter::for(static::LIVEWIRE, fn (Request $request) => static::limits($request, static::LIVEWIRE));
    }

    /**
     * @return array<int, Limit>
     */
    public static function limits(Request $request, string $name): array
    {
        $user = $request->user();

        if (UploadPolicy::isPanelUser($user)) {
            return [Limit::perMinute((int) config('dashed-core.uploads.rate_limit.panel_per_minute', 120))->by($name . ':user:' . $user->getAuthIdentifier())];
        }

        $perMinute = (int) config('dashed-core.uploads.rate_limit.guest_per_minute', 30);
        $limits = [Limit::perMinute($perMinute)->by($name . ':ip:' . $request->ip())];

        if ($user) {
            $limits[] = Limit::perMinute($perMinute)->by($name . ':user:' . $user->getAuthIdentifier());
        }

        return $limits;
    }

    /**
     * Past de limiet toe binnen een Livewire-actie (daar is geen
     * route-middleware). False = limiet bereikt.
     */
    public static function attempt(Request $request, string $name): bool
    {
        $limits = static::limits($request, $name);

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit->key, $limit->maxAttempts)) {
                return false;
            }
        }

        foreach ($limits as $limit) {
            RateLimiter::hit($limit->key, $limit->decaySeconds);
        }

        return true;
    }
}

<?php

namespace Dashed\DashedCore\Classes\Caching;

use Illuminate\Http\Request;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Vite;
use Dashed\DashedCore\Classes\CacheProfile;

class CacheDecision
{
    private const NEVER_CACHE_PREFIXES = [
        'checkout',
        'cart',
        'account',
        'api',
        'livewire',
        'dashed',
        'return-status',    // return-status/{hash} - order return page (hash-guarded, not auth)
        'proforma',         // proforma/{orderHash} - proforma checkout (hash-guarded)
        'pay',              // pay/order/{orderHash}/remainder - remainder payment (hash-guarded)
        'recover-order',    // recover-order/{order} - abandoned cart recovery
        'restore-cart',     // restore-cart - cart restore
        'download-invoice', // download-invoice/{orderHash} - invoice PDF (hash-guarded)
        'download-packing-slip', // download-packing-slip/{orderHash} - packing slip PDF (hash-guarded)
        'ecommerce',        // ecommerce/orders/exchange - payment provider callback
    ];

    private function __construct(
        private readonly bool $shouldCache,
        private readonly string $cacheKey,
        private readonly int $ttl,
        private readonly array $tags,
        private readonly string $reason,
    ) {
    }

    /**
     * De sleutel draagt de vingerafdruk van de gebouwde assets mee. Zonder dat
     * blijft gecachete HTML bestaan waarin de gehashte naam van een stylesheet
     * staat die de volgende build heeft weggegooid: de pagina laadt dan een
     * 404'ende CSS en komt zonder opmaak binnen, terwijl elke nog niet
     * gecachete pagina er goed uitziet. Een nieuwe build geeft nu vanzelf
     * nieuwe sleutels, dus die HTML wordt simpelweg niet meer gevonden en
     * handmatig legen na een deploy is niet meer nodig.
     */
    public static function cacheKeyFor(string $siteId, string $locale, string $path): string
    {
        return 'response:' . $siteId . ':' . $locale . ':' . self::assetsFingerprint() . ':' . sha1($path);
    }

    /**
     * Vite geeft niets terug zonder build en met de dev-server aan; in beide
     * gevallen valt er niets te verlopen en volstaat een vaste waarde.
     */
    private static function assetsFingerprint(): string
    {
        return Vite::manifestHash() ?: 'no-build';
    }

    public static function for(Request $request): self
    {
        $siteId = Sites::getActive();
        $profile = CacheProfile::forSite($siteId);
        $locale = app()->getLocale();
        $key = self::cacheKeyFor($siteId, $locale, $request->getPathInfo());
        $tags = ['response', 'response:site:' . $siteId];
        $deny = fn (string $why) => new self(false, $key, 0, $tags, $why);

        // 1. Kill-switch
        if (! config('dashed-core.response_cache_enabled', false)) {
            return $deny('kill-switch off');
        }

        // 2. Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $deny('not GET');
        }

        // 3. Profile must allow response caching
        if (! $profile->responseCache()) {
            return $deny('profile ' . $profile->name() . ' no response cache');
        }

        // 4. Never-cache route prefixes
        $path = trim($request->getPathInfo(), '/');
        foreach (self::NEVER_CACHE_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $deny('never-cache route ' . $prefix);
            }
        }

        // 5. Querystring (v1: never cache URLs with query params)
        if ($request->getQueryString()) {
            return $deny('has querystring');
        }

        // 6. Identified cookie (tracking cookie set for known visitors)
        if ($request->cookies->has('dashed_identified')) {
            return $deny('identified cookie');
        }

        // 7. Logged-in users (when profile says bypass)
        if ($profile->bypassWhenLoggedIn() && auth()->check()) {
            return $deny('logged in');
        }

        // 8. Price group / custom pricing
        if ($profile->bypassPriceGroups() && auth()->check()
            && (auth()->user()->price_group_id || auth()->user()->has_custom_pricing)) {
            return $deny('price group');
        }

        return new self(true, $key, $profile->responseTtl(), $tags, 'cacheable');
    }

    public function shouldCache(): bool
    {
        return $this->shouldCache;
    }

    public function cacheKey(): string
    {
        return $this->cacheKey;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}

<?php

namespace Dashed\DashedCore\Classes\Caching;

use Dashed\DashedCore\Classes\CacheProfile;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Http\Request;

class CacheDecision
{
    private const NEVER_CACHE_PREFIXES = ['checkout', 'cart', 'account', 'api', 'livewire', 'dashed'];

    private function __construct(
        private readonly bool $shouldCache,
        private readonly string $cacheKey,
        private readonly int $ttl,
        private readonly array $tags,
        private readonly string $reason,
    ) {}

    public static function for(Request $request): self
    {
        $siteId = Sites::getActive();
        $profile = CacheProfile::forSite($siteId);
        $locale = app()->getLocale();
        $key = 'response:' . $siteId . ':' . $locale . ':' . sha1($request->getPathInfo());
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

        // 6. Logged-in users (when profile says bypass)
        if ($profile->bypassWhenLoggedIn() && auth()->check()) {
            return $deny('logged in');
        }

        // 7. Identified cookie (tracking cookie set for known visitors)
        if ($request->cookies->has('dashed_identified')) {
            return $deny('identified cookie');
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

<?php

namespace Dashed\DashedCore\Classes;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FragmentCache
{
    protected static ?bool $supportsTags = null;

    public static function supportsTags(): bool
    {
        if (static::$supportsTags !== null) {
            return static::$supportsTags;
        }

        try {
            Cache::tags(['__fragmentcache_probe'])->get('__probe');

            return static::$supportsTags = true;
        } catch (\BadMethodCallException) {
            return static::$supportsTags = false;
        }
    }

    public static function remember(string $key, array $tags, int $ttl, Closure $callback): mixed
    {
        if (static::supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    public static function flushTag(string $tag): void
    {
        if (static::supportsTags()) {
            Cache::tags([$tag])->flush();

            return;
        }

        Log::debug("FragmentCache::flushTag('{$tag}') no-op: cache store zonder tag-ondersteuning.");
    }
}

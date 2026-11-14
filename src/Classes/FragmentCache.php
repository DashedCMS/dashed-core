<?php

namespace Dashed\DashedCore\Classes;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Classes\Caching\ResponseCacheStore;

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
        }

        // Pagina's van de responsecache staan zonder tags in een eigen map (ResponseCacheStore):
        // elke response-tag leegt die map in zijn geheel (grofmazig, net als flushSite zelf).
        if (str_starts_with($tag, 'response') && ! ResponseCacheStore::usesTags()) {
            ResponseCacheStore::flush();

            return;
        }

        if (static::supportsTags()) {
            return;
        }

        Log::debug("FragmentCache::flushTag('{$tag}') no-op: cache store zonder tag-ondersteuning.");
    }
}

<?php

namespace Dashed\DashedCore\Classes\Caching;

use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository;
use Dashed\DashedCore\Classes\FragmentCache;

/**
 * Waar de responsecache zijn pagina's bewaart.
 *
 * Pagina's worden geleegd via cache-tags (CacheInvalidator → FragmentCache::flushTag('response…')).
 * Op een store zonder tags (file, database) kan dat niet, en in de standaardstore zou een pagina na
 * een prijs- of productwijziging tot de TTL oud blijven; de hele standaardstore legen is geen optie
 * (instellingen, vertalingen, locks en rate limits staan daar ook). Daarom:
 *
 *  - met tags (redis, memcached, array): in de standaardstore onder de tags van de beslissing;
 *  - zonder tags: in een eigen file-store met een eigen map, die in één keer leeg gaat bij elke
 *    flush van een response-tag, bij `cache:clear` en elke nacht (de file-store ruimt verlopen
 *    bestanden niet zelf op).
 *
 * Instelbaar met `dashed-core.response_cache_store`: 'auto' (standaard), 'tags' of 'own'.
 * De waarde wordt gecomprimeerd opgeslagen (een pagina van ± 300 kB wordt ± 40 kB).
 */
class ResponseCacheStore
{
    public const STORE = 'dashed_responsecache';

    private const GZ = '__dashed_gz';

    public static function usesTags(): bool
    {
        return match (config('dashed-core.response_cache_store', 'auto')) {
            'tags' => true,
            'own' => false,
            default => FragmentCache::supportsTags(),
        };
    }

    public static function path(): string
    {
        return (string) (config('dashed-core.response_cache_path') ?: storage_path('framework/cache/responsecache'));
    }

    /** Meldt de eigen file-store aan in de cacheconfig (als een project hem niet zelf definieert). */
    public static function registerStore(): void
    {
        if (! config('cache.stores.' . self::STORE)) {
            config(['cache.stores.' . self::STORE => [
                'driver' => 'file',
                'path' => self::path(),
            ]]);
        }
    }

    public static function get(array $tags, string $key): mixed
    {
        return self::unpack(self::repository($tags)->get($key));
    }

    public static function put(array $tags, string $key, mixed $value, int $ttl): void
    {
        self::repository($tags)->put($key, self::pack($value), max(1, $ttl));
    }

    /** Leegt de eigen map. Met tags leegt FragmentCache::flushTag() de pagina's zelf. */
    public static function flush(): void
    {
        self::own()->flush();
    }

    public static function own(): Repository
    {
        return Cache::store(self::STORE);
    }

    public static function pack(mixed $value): mixed
    {
        if (! function_exists('gzencode')) {
            return $value;
        }

        $gz = gzencode(serialize($value), 1);

        return $gz === false ? $value : [self::GZ => $gz];
    }

    public static function unpack(mixed $stored): mixed
    {
        if (! is_array($stored) || ! isset($stored[self::GZ]) || ! is_string($stored[self::GZ])) {
            return $stored;
        }

        if (! str_starts_with($stored[self::GZ], "\x1f\x8b")) {
            return null;
        }

        $raw = @gzdecode($stored[self::GZ]);

        return $raw === false ? null : unserialize($raw, ['allowed_classes' => false]);
    }

    protected static function repository(array $tags): Repository
    {
        return self::usesTags() ? Cache::tags($tags) : self::own();
    }
}

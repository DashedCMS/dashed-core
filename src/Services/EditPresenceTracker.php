<?php

namespace Dashed\DashedCore\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache-backed presence tracker for Filament edit pages. Multiple
 * admins opening the same record's edit page register a short-lived
 * heartbeat, so the UI can surface a "Robin is already editing this"
 * banner.
 *
 * Cache shape per record:
 *   key:   dashed:editing:{resourceKey}:{recordKey}
 *   value: array<int, array{name: string, last_seen: int}> keyed by user id
 *   ttl:   60 seconds (refreshed on every ping)
 */
class EditPresenceTracker
{
    /**
     * How long an entry remains valid without a refresh. Heartbeats run
     * shorter than this so a tab that closes drops out within one cycle.
     */
    public const TTL_SECONDS = 60;

    public function ping(string $resourceKey, string $recordKey, int $userId, string $userName): void
    {
        $key = $this->cacheKey($resourceKey, $recordKey);
        $now = time();

        $editors = $this->fresh(Cache::get($key, []), $now);

        $editors[$userId] = [
            'name' => $userName,
            'last_seen' => $now,
        ];

        Cache::put($key, $editors, self::TTL_SECONDS);
    }

    /**
     * @return array<int, array{name: string, last_seen: int}>
     */
    public function currentEditors(string $resourceKey, string $recordKey, int $excludeUserId): array
    {
        $editors = $this->fresh(
            Cache::get($this->cacheKey($resourceKey, $recordKey), []),
            time(),
        );

        unset($editors[$excludeUserId]);

        return $editors;
    }

    public function release(string $resourceKey, string $recordKey, int $userId): void
    {
        $key = $this->cacheKey($resourceKey, $recordKey);
        $editors = Cache::get($key, []);

        if (! isset($editors[$userId])) {
            return;
        }

        unset($editors[$userId]);

        if ($editors === []) {
            Cache::forget($key);

            return;
        }

        Cache::put($key, $editors, self::TTL_SECONDS);
    }

    /**
     * @param  array<int, array{name: string, last_seen: int}>  $editors
     * @return array<int, array{name: string, last_seen: int}>
     */
    protected function fresh(array $editors, int $now): array
    {
        $cutoff = $now - self::TTL_SECONDS;

        return array_filter(
            $editors,
            fn (array $entry) => ($entry['last_seen'] ?? 0) >= $cutoff,
        );
    }

    protected function cacheKey(string $resourceKey, string $recordKey): string
    {
        return 'dashed:editing:' . sha1($resourceKey) . ':' . $recordKey;
    }
}

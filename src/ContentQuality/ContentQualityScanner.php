<?php

// packages/dashed/dashed-core/src/ContentQuality/ContentQualityScanner.php

namespace Dashed\DashedCore\ContentQuality;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContentQualityScanner
{
    public const TTL = 600;

    public function __construct(protected ContentQualityRegistry $registry)
    {
    }

    /** @return array<string, array{label: string, count: int, resolutions: array}> */
    public function counts(string $siteId): array
    {
        $out = [];
        foreach ($this->registry->checks() as $check) {
            $count = Cache::remember(
                $this->cacheKey($siteId, $check->key(), 'count'),
                self::TTL,
                fn () => $check->count($siteId),
            );

            $out[$check->key()] = [
                'label' => $check->label(),
                'count' => $count,
                'resolutions' => $check->resolutions(),
            ];
        }

        return $out;
    }

    /** @return Collection<int, QualityIssue> */
    public function issues(string $siteId, string $checkKey): Collection
    {
        $check = $this->registry->check($checkKey);
        if (! $check) {
            return collect();
        }

        return Cache::remember(
            $this->cacheKey($siteId, $checkKey, 'items'),
            self::TTL,
            fn () => $check->items($siteId),
        );
    }

    public function rescan(string $siteId): void
    {
        foreach ($this->registry->checks() as $check) {
            Cache::forget($this->cacheKey($siteId, $check->key(), 'count'));
            Cache::forget($this->cacheKey($siteId, $check->key(), 'items'));
        }
    }

    protected function cacheKey(string $siteId, string $checkKey, string $suffix): string
    {
        return "content-quality:{$siteId}:{$checkKey}:{$suffix}";
    }
}

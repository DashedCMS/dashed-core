<?php

// packages/dashed/dashed-core/src/ContentQuality/Contracts/ContentQualityCheck.php

namespace Dashed\DashedCore\ContentQuality\Contracts;

use Illuminate\Support\Collection;

interface ContentQualityCheck
{
    /** Stable machine key, e.g. 'missing_meta_title'. */
    public function key(): string;

    /** Dutch label for the stat card, e.g. 'Meta-titel ontbreekt'. */
    public function label(): string;

    /** Number of issues for the given site (used by the stat card). */
    public function count(string $siteId): int;

    /** @return Collection<int, \Dashed\DashedCore\ContentQuality\QualityIssue> */
    public function items(string $siteId): Collection;

    /** Subset of: 'inline', 'ai', 'bulk_ai', 'link'. */
    public function resolutions(): array;
}

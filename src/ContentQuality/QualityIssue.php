<?php

// packages/dashed/dashed-core/src/ContentQuality/QualityIssue.php

namespace Dashed\DashedCore\ContentQuality;

class QualityIssue
{
    public function __construct(
        public string $checkKey,
        public string $title,
        public string $subtitle = '',
        public ?string $editUrl = null,
        public ?int $mediaId = null,
        public ?string $modelClass = null,
        public int|string|null $modelId = null,
        public array $missingLocales = [],
    ) {
    }
}

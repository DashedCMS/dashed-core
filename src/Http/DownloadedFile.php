<?php

namespace Dashed\DashedCore\Http;

final class DownloadedFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $mime,
        public readonly int $size,
        public readonly string $finalUrl,
        public readonly ?string $suggestedName,
    ) {
    }
}

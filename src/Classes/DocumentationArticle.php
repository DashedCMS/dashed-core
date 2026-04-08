<?php

namespace Dashed\DashedCore\Classes;

class DocumentationArticle
{
    public function __construct(
        public string $package,
        public string $path,
        public string $title,
        public string $description,
        public string $htmlContent,
        public string $rawContent,
        public string $sectionLabel,
        public string $packageLabel,
    ) {
    }
}

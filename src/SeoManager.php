<?php

namespace Qubiqx\QcommerceCore;

class SeoManager
{
    protected static $metaData = [
        'metaTitle' => '',
        'metaDescription' => '',
        'metaImage' => '',
        'ogType' => 'website',
        'twitterSite' => '',
        'twitterCreator' => '',
        'webmasterTags' => [],
        'robots' => 'index, follow',
        'schema' => '',
        'alternateUrls' => [],
    ];

    public function metaData(string $name, string|array $value = null): self|string|array
    {
        if (! $value) {
            return static::$metaData[$name] ?? '';
        }

        static::$metaData[$name] = $value;

        return $this;
    }
}

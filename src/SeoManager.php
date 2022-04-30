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
    ];

    public function metaData(string $name, ?string $value = null): self|string
    {
        if (! $value) {
            return static::$metaData[$name] ?? '';
        }

        static::$metaData[$name] = $value;

        return $this;
    }
}

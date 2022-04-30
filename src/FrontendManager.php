<?php

namespace Qubiqx\QcommerceCore;

class FrontendManager
{
    protected static $metaData = [
        'metaTitle' => '',
        'metaDescription' => '',
        'metaImage' => '',
    ];

    public function metaData(string $name, ?string $value = null): self|string
    {
        if (! $value) {
            return static::$metaData[$name] ?? [];
        }

        static::$metaData[$name] = $value;

        return $this;
    }
}

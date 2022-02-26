<?php

namespace Qubiqx\QcommerceCore;

class CMSManager
{
    protected static $models = [];

    protected static $builders = [
        'sites' => [],
        'forms' => [],
        'blocks' => [],
        'content' => [],
        'routeModels' => [],
        'settingPages' => [],
        'frontendMiddlewares' => [],
    ];

    public function model(string $name, ?string $implementation = null): self|string
    {
        if (! $implementation) {
            return static::$models[$name];
        }

        static::$models[$name] = $implementation;

        return $this;
    }

    public function builder(string $name, ?array $blocks = null): self|array
    {
        if (! $blocks) {
            return static::$builders[$name];
        }

        static::$builders[$name] = $blocks;

        return $this;
    }
}

<?php

namespace Qubiqx\QcommerceCore;

use Qubiqx\QcommerceCore\Models\Menu;
use Qubiqx\QcommercePages\Models\Page;
use Qubiqx\QcommerceCore\Models\MenuItem;

class CMSManager
{
    protected static $models = [
        'Page' => Page::class,
        'Menu' => Menu::class,
        'MenuItem' => MenuItem::class,
    ];

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

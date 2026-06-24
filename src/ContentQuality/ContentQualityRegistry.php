<?php

// packages/dashed/dashed-core/src/ContentQuality/ContentQualityRegistry.php

namespace Dashed\DashedCore\ContentQuality;

use Dashed\DashedCore\ContentQuality\Contracts\ContentQualityCheck;

/**
 * In-memory registry of content-quality checks and the visitable models
 * the generic meta checks scan. Bound as a singleton in the register()
 * phase so every package SP's bootingPackage() registers into the same
 * instance.
 */
class ContentQualityRegistry
{
    /** @var array<string, ContentQualityCheck> */
    protected array $checks = [];

    /** @var array<int, RegisteredModel> */
    protected array $models = [];

    public function registerCheck(ContentQualityCheck $check): void
    {
        $this->checks[$check->key()] = $check;
    }

    public function registerModel(RegisteredModel $model): void
    {
        $this->models[] = $model;
    }

    /** @return array<int, ContentQualityCheck> */
    public function checks(): array
    {
        return array_values($this->checks);
    }

    public function check(string $key): ?ContentQualityCheck
    {
        return $this->checks[$key] ?? null;
    }

    /** @return array<int, RegisteredModel> */
    public function models(): array
    {
        return $this->models;
    }

    public function flush(): void
    {
        $this->checks = [];
        $this->models = [];
    }
}

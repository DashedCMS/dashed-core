<?php

namespace Dashed\DashedCore\Services\VisualEditor;

class EditContextStack
{
    /** @var array<int, array{model_type: string, model_id: int|string, locale: string, block: int}> */
    private array $stack = [];

    public function push(string $modelType, int|string $modelId, string $locale, int $block): void
    {
        $this->stack[] = [
            'model_type' => $modelType,
            'model_id' => $modelId,
            'locale' => $locale,
            'block' => $block,
        ];
    }

    public function pop(): void
    {
        array_pop($this->stack);
    }

    /** @return array{model_type: string, model_id: int|string, locale: string, block: int}|null */
    public function current(): ?array
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }
}

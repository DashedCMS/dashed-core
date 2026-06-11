<?php

namespace Dashed\DashedCore\Services\VisualEditor;

use Illuminate\Support\Str;
use Filament\Forms\Components\Builder\Block;

class BlockSchemaResolver
{
    public function resolve(string $type): ?Block
    {
        // 'blocks' is de canonieke set; per-resource registry's volgen de
        // conventie 'xxxBlocks' (pageBlocks, productBlocks, ...). We scannen
        // elke geregistreerde builder-key die aan die conventie voldoet, zodat
        // nieuwe resources met een eigen blokken-registry automatisch werken.
        foreach (cms()->builderKeys() as $name) {
            if ($name !== 'blocks' && ! Str::endsWith($name, 'Blocks')) {
                continue;
            }

            foreach ((array) (cms()->builder($name) ?? []) as $block) {
                if ($block instanceof Block && $block->getName() === $type) {
                    return $block;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, mixed>  De child-componenten (veld-schema) van het blok-type, of [].
     */
    public function components(string $type): array
    {
        $block = $this->resolve($type);

        return $block ? $block->getClone()->getDefaultChildComponents() : [];
    }
}

<?php

namespace Dashed\DashedCore\Services\VisualEditor;

use Filament\Forms\Components\Builder\Block;

class BlockSchemaResolver
{
    /**
     * Builder-registry-namen die blok-schema's bevatten. 'blocks' is de
     * canonieke set; de overige namen komen overeen met de namen die de
     * resources aan customBlocksTab(...) doorgeven (pageBlocks, productBlocks,
     * articleBlocks, ...). Vul aan als er nieuwe resources met een eigen
     * blokken-registry bijkomen.
     *
     * @var array<int, string>
     */
    protected array $builderNames = [
        'blocks',
        'pageBlocks',
        'productBlocks',
        'productGroupBlocks',
        'productFilterBlocks',
        'productExtraOptionBlocks',
        'productFaqBlocks',
        'productTabBlocks',
        'productCategoryBlocks',
        'articleBlocks',
        'articleAuthorBlocks',
        'articleCategoryBlocks',
        'vacancyBlocks',
        'vacancyCategoryBlocks',
        'menuItemBlocks',
        'formBlocks',
        'formFieldBlocks',
    ];

    public function resolve(string $type): ?Block
    {
        foreach ($this->builderNames as $name) {
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

        return $block ? $block->getDefaultChildComponents() : [];
    }
}

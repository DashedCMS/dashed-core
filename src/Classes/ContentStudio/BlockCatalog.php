<?php

namespace Dashed\DashedCore\Classes\ContentStudio;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Builder\Block;

class BlockCatalog
{
    public function for(string $blocksName): array
    {
        return $this->fromBlocks(cms()->builder($blocksName) ?: []);
    }

    /** @param array<int, Block> $blocks */
    public function fromBlocks(array $blocks): array
    {
        $excluded = config('dashed-content-studio.excluded_blocks', []);
        $overrides = config('dashed-content-studio.overrides', []);
        $catalog = [];

        foreach ($blocks as $block) {
            if (! $block instanceof Block) {
                continue;
            }

            $type = $block->getName();

            if (in_array($type, $excluded, true)) {
                continue;
            }

            if (isset($overrides[$type])) {
                $fields = $overrides[$type];
            } else {
                $fields = array_map(
                    fn (FieldDescriptor $f) => $f->toArray(),
                    $this->describeComponents($this->childComponents($block)),
                );
            }

            $catalog[] = [
                'type' => $type,
                'label' => (string) ($block->getLabel() ?: $type),
                'fields' => array_values($fields),
            ];
        }

        return $catalog;
    }

    /**
     * SPIKE (Filament v4.3, deze install): de Builder\Block leeft in de
     * `Filament\Forms\Components\Builder` namespace (NIET `Schemas`).
     * getChildComponents()/getChildComponentContainers() gooien
     * "Typed property ...::$container must not be accessed before
     * initialization" op een nog niet-gekoppelde block. De juiste accessor
     * die de platte lijst child-componenten in declaratie-volgorde teruggeeft
     * is getDefaultChildComponents().
     *
     * @return array<int, mixed>
     */
    private function childComponents(Block $block): array
    {
        return $block->getDefaultChildComponents();
    }

    /**
     * @param array<int, mixed> $components
     * @return array<int, FieldDescriptor>
     */
    private function describeComponents(array $components): array
    {
        $fields = [];
        foreach ($components as $component) {
            $descriptor = $this->describe($component);
            if ($descriptor !== null) {
                $fields[] = $descriptor;
            }
        }

        return $fields;
    }

    private function describe(mixed $component): ?FieldDescriptor
    {
        if (! is_object($component) || ! method_exists($component, 'getName')) {
            return null;
        }

        $name = $component->getName();
        $label = method_exists($component, 'getLabel') ? (string) ($component->getLabel() ?: $name) : $name;

        return match (true) {
            $component instanceof RichEditor => new FieldDescriptor($name, 'rich', $label),
            $component instanceof Textarea => new FieldDescriptor($name, 'textarea', $label),
            $component instanceof Toggle => new FieldDescriptor($name, 'toggle', $label),
            $component instanceof Select => new FieldDescriptor($name, 'select', $label, options: $this->selectOptions($component)),
            $component instanceof TextInput => new FieldDescriptor($name, 'text', $label),
            $this->isImageField($component) => new FieldDescriptor($name, 'image', $label),
            default => null,
        };
    }

    private function selectOptions(Select $component): ?array
    {
        try {
            $options = $component->getOptions();

            return is_array($options) ? $options : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isImageField(mixed $component): bool
    {
        $class = $component::class;

        return str_contains($class, 'MediaPicker') || str_contains($class, 'FileUpload');
    }
}

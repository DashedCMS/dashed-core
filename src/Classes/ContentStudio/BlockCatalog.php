<?php

namespace Dashed\DashedCore\Classes\ContentStudio;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Builder\Block;

class BlockCatalog
{
    /**
     * Veiligheidslimiet tegen pathologisch diepe component-bomen.
     */
    private const MAX_DEPTH = 6;

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
                'label' => $this->safeLabel($block, $type),
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
     * Loopt platte lijst child-componenten af en bouwt FieldDescriptors. Per
     * component, in deze volgorde:
     *   1. Repeater  -> één descriptor met geneste velden in `of`.
     *   2. Herkend leaf-veld -> één descriptor.
     *   3. Layout-wrapper (Grid/Section/Fieldset/...) met child-componenten ->
     *      platgeslagen: de binnenste velden komen op de plek van de wrapper.
     *   4. Anders -> overgeslagen.
     *
     * @param array<int, mixed> $components
     * @return array<int, FieldDescriptor>
     */
    private function describeComponents(array $components, int $depth = 0): array
    {
        if ($depth > self::MAX_DEPTH) {
            return [];
        }

        $fields = [];
        foreach ($components as $component) {
            if (! is_object($component)) {
                continue;
            }

            // 1. Repeater: beschrijf het zelf en recurse in de child-schema.
            if ($component instanceof Repeater) {
                $name = $component->getName();
                $label = $this->safeLabel($component, $name);
                $fields[] = new FieldDescriptor(
                    $name,
                    'repeater',
                    $label,
                    of: $this->describeComponents($this->safeChildComponents($component), $depth + 1),
                );

                continue;
            }

            // 2. Herkend leaf-veld.
            $descriptor = $this->describe($component);
            if ($descriptor !== null) {
                $fields[] = $descriptor;

                continue;
            }

            // 3. Layout-wrapper: platslaan als er child-componenten zijn.
            $children = $this->safeChildComponents($component);
            if ($children !== []) {
                foreach ($this->describeComponents($children, $depth + 1) as $childField) {
                    $fields[] = $childField;
                }
            }

            // 4. Anders: overslaan.
        }

        return $fields;
    }

    /**
     * Haalt de directe child-componenten op; geeft een lege array terug als de
     * component dit niet ondersteunt of erop gooit (bv. niet-gekoppelde state).
     *
     * @return array<int, mixed>
     */
    private function safeChildComponents(mixed $component): array
    {
        if (! is_object($component) || ! method_exists($component, 'getDefaultChildComponents')) {
            return [];
        }

        try {
            $children = $component->getDefaultChildComponents();

            return is_array($children) ? $children : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function describe(mixed $component): ?FieldDescriptor
    {
        if (! is_object($component) || ! method_exists($component, 'getName')) {
            return null;
        }

        $name = $component->getName();
        $label = $this->safeLabel($component, $name);

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

    private function safeLabel(object $component, string $fallback): string
    {
        try {
            $label = method_exists($component, 'getLabel') ? $component->getLabel() : null;
        } catch (\Throwable) {
            $label = null;
        }

        return (string) ($label ?: $fallback);
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

<?php

namespace Dashed\DashedCore\Classes\Editor;

use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\PageTemplate;
use Dashed\DashedCore\Filament\Components\ContentBuilder;

/**
 * Blokken van en naar een paginasjabloon. Een sjabloon bewaart de blokken
 * als gewone lijst: de uuid-sleutels van Filament (bovenaan en in repeaters
 * en geneste builders) zijn eigen aan één formulier en worden genummerde
 * items, net als removeUUIDKeys() in HasEditableCMSActions bij kopiëren
 * doet. De markering voor ongemoeide blokken gaat er ook af.
 */
class TemplateBlocks
{
    private const UUID = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function sanitize(mixed $content): array
    {
        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        if (! is_array($content)) {
            return [];
        }

        $content = ContentBuilder::withoutUntouchedMarkers($content);

        $blocks = static::stripUuidKeys($content);

        return array_values(array_filter($blocks, fn ($block) => is_array($block) && isset($block['type'])));
    }

    public static function stripUuidKeys(array $array): array
    {
        $new = [];

        foreach ($array as $key => $value) {
            if ($key === ContentBuilder::UNTOUCHED_MARKER) {
                continue;
            }

            $value = is_array($value) ? static::stripUuidKeys($value) : $value;

            if (is_string($key) && preg_match(self::UUID, $key)) {
                $new[] = $value;
            } else {
                $new[$key] = $value;
            }
        }

        return $new;
    }

    /**
     * De opgeslagen blokken van een model in één taal.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fromModel(Model $record, string $locale): array
    {
        $content = method_exists($record, 'getTranslation') && in_array('content', $record->translatable ?? [], true)
            ? $record->getTranslation('content', $locale, false)
            : $record->getAttribute('content');

        return static::sanitize($content);
    }

    public static function saveFromModel(Model $record, string $locale, string $name, ?string $description = null, bool $forThisTypeOnly = true): PageTemplate
    {
        return PageTemplate::create([
            'name' => $name,
            'description' => $description,
            'model_type' => $forThisTypeOnly ? $record::class : null,
            'content' => static::fromModel($record, $locale),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Een nieuw model met de blokken van het sjabloon in de gegeven taal.
     * Slug en sites vult IsVisitable zelf bij het opslaan.
     */
    public static function createModel(string $class, PageTemplate $template, string $name, string $locale): Model
    {
        /** @var Model $model */
        $model = new $class();
        $translatable = $model->translatable ?? [];

        if (in_array('name', $translatable, true)) {
            $model->setTranslation('name', $locale, $name);
        } else {
            $model->setAttribute('name', $name);
        }

        $blocks = static::sanitize($template->content);

        if (in_array('content', $translatable, true)) {
            $model->setTranslation('content', $locale, $blocks);
        } else {
            $model->setAttribute('content', $blocks);
        }

        $model->save();

        return $model;
    }

    /**
     * @return array<int|string, string>
     */
    public static function options(?string $class): array
    {
        return PageTemplate::query()->forModel($class)->orderBy('name')->pluck('name', 'id')->all();
    }
}

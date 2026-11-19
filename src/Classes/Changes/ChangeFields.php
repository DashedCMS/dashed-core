<?php

namespace Dashed\DashedCore\Classes\Changes;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Register van welke velden per model ingepland of in bulk bewerkt kunnen
 * worden. Een pakket meldt zich aan vanuit zijn boot-fase (nooit
 * register(), de labels zijn __()-aanroepen):
 *
 *   ChangeFields::register(Product::class, fn () => [ChangeField::make('price')->label(__('Prijs'))->number(0.01), ...], fn () => __('Product'));
 *
 * Een model met de trait IsVisitable dat zich niet aanmeldt, krijgt
 * standaard zijn titel en inhoud (als die vertaalbaar zijn), zodat elke
 * pagina, elk artikel en elke categorie zonder eigen code een
 * "Wijziging inplannen" heeft.
 */
class ChangeFields
{
    /** @var array<string, array{fields: Closure|array, label: Closure|string|null}> */
    protected static array $registry = [];

    public static function register(string $modelClass, Closure|array $fields, Closure|string|null $label = null): void
    {
        static::$registry[$modelClass] = ['fields' => $fields, 'label' => $label];
    }

    public static function forget(string $modelClass): void
    {
        unset(static::$registry[$modelClass]);
    }

    /**
     * @param  'schedule'|'bulk'|null  $context
     * @return array<string, ChangeField>
     */
    public static function for(Model|string $model, ?string $context = null): array
    {
        $class = static::classOf($model);
        $entry = static::entry($class);

        if ($entry) {
            $fields = $entry['fields'] instanceof Closure ? ($entry['fields'])() : $entry['fields'];
        } else {
            $fields = static::defaults($class);
        }

        $fields = collect($fields)
            ->filter(fn (ChangeField $field) => match ($context) {
                'schedule' => $field->isSchedulable(),
                'bulk' => $field->isBulkEditable(),
                default => true,
            })
            ->keyBy(fn (ChangeField $field) => $field->getKey())
            ->all();

        return $fields;
    }

    public static function supports(Model|string $model, ?string $context = null): bool
    {
        return count(static::for($model, $context)) > 0;
    }

    public static function modelLabel(Model|string $model): string
    {
        $class = static::classOf($model);
        $label = static::entry($class)['label'] ?? null;

        if ($label instanceof Closure) {
            $label = $label();
        }

        if ($label) {
            return (string) $label;
        }

        $resource = rescue(fn () => Filament::getModelResource($class), null, false);

        return $resource ? (string) $resource::getModelLabel() : class_basename($class);
    }

    public static function recordLabel(?Model $model, string $fallbackId = ''): string
    {
        if (! $model) {
            return __('Verwijderd record #:id', ['id' => $fallbackId]);
        }

        $name = $model->getAttribute('name') ?? $model->getAttribute('title');

        if (is_array($name)) {
            $name = reset($name);
        }

        return $name ? (string) $name : '#' . $model->getKey();
    }

    public static function recordUrl(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        return rescue(function () use ($model) {
            $resource = Filament::getModelResource($model);

            return $resource && $resource::hasPage('edit') ? $resource::getUrl('edit', ['record' => $model]) : null;
        }, null, false);
    }

    /**
     * Het record achter een morph-type en id, ook als het soft-deleted is.
     */
    public static function find(string $modelType, string|int $id): ?Model
    {
        $class = Relation::getMorphedModel($modelType) ?? $modelType;

        if (! class_exists($class)) {
            return null;
        }

        /** @var Builder $query */
        $query = $class::query();

        if (method_exists($class, 'bootSoftDeletes')) {
            $query->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
        }

        return $query->find($id);
    }

    protected static function classOf(Model|string $model): string
    {
        if ($model instanceof Model) {
            return $model::class;
        }

        return Relation::getMorphedModel($model) ?? $model;
    }

    /**
     * @return array{fields: Closure|array, label: Closure|string|null}|null
     */
    protected static function entry(string $class): ?array
    {
        if (isset(static::$registry[$class])) {
            return static::$registry[$class];
        }

        // Een klantproject dat het model erft (App\Models\Product) hoort de
        // velden van het basismodel te krijgen.
        foreach (static::$registry as $registered => $entry) {
            if (is_subclass_of($class, $registered)) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array<int, ChangeField>
     */
    protected static function defaults(string $class): array
    {
        if (! class_exists($class) || ! in_array(\Dashed\DashedCore\Models\Concerns\IsVisitable::class, class_uses_recursive($class), true)) {
            return [];
        }

        $translatable = (array) ((new $class())->translatable ?? []);
        $fields = [];

        if (in_array('name', $translatable, true)) {
            $fields[] = ChangeField::make('name')->label(fn () => __('Titel'))->text()->translatable()->onlyScheduled();
        }

        if (in_array('content', $translatable, true)) {
            $fields[] = ChangeField::make('content')
                ->label(fn () => __('Inhoud'))
                ->content()
                ->helperText(fn () => __('De inhoud wordt bij het uitvoeren overgenomen van het gekozen record, in de taal waarin je nu bewerkt. Bereid de nieuwe inhoud bijvoorbeeld voor op een verborgen kopie.'));
        }

        return $fields;
    }
}

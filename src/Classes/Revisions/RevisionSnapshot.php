<?php

namespace Dashed\DashedCore\Classes\Revisions;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

/**
 * Neemt en herstelt een momentopname van een record zoals hij in de database
 * staat: altijd uit een verse query, nooit uit de formulierstaat. Zo komt de
 * markering voor ongemoeide blokken (ContentBuilder::UNTOUCHED_MARKER) er
 * nooit in, want die bestaat alleen in de Livewire-staat.
 *
 * Wat erin zit:
 * - een model dat in de goedkeuringsstroom is aangemeld: alle kolommen behalve
 *   id, tijdstempels en `$revisionExcept`, want een concept moet ook `public`,
 *   datums en sites kunnen dragen;
 * - elk ander model: alleen de vertaalbare kolommen (content hoort daarbij).
 *   Bewust: terugzetten van een product mag nooit een oude voorraad of prijs
 *   terugzetten.
 * - de relaties uit `$revisionRelations` (standaard metadata en customBlocks),
 *   met al hun kolommen behalve sleutels en tijdstempels.
 */
class RevisionSnapshot
{
    public const EXCLUDED = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /** @var array<string, array<int, string>> */
    protected static array $columns = [];

    /**
     * @return array<int, string>
     */
    public static function relationsFor(Model $model): array
    {
        $relations = property_exists($model, 'revisionRelations')
            ? (array) $model->revisionRelations
            : ['metadata', 'customBlocks'];

        return array_values(array_filter(
            $relations,
            fn (string $relation) => method_exists($model, $relation) && $model->{$relation}() instanceof MorphOne,
        ));
    }

    /**
     * Null betekent: alle kolommen.
     *
     * @return array<int, string>|null
     */
    public static function attributeKeysFor(Model $model): ?array
    {
        if (ContentApproval::isRegistered($model::class)) {
            return null;
        }

        return method_exists($model, 'getTranslatableAttributes')
            ? array_values($model->getTranslatableAttributes())
            : [];
    }

    public static function take(Model $model): array
    {
        $fresh = $model->newQueryWithoutScopes()->whereKey($model->getKey())->first() ?? $model;

        $snapshot = [
            'attributes' => static::attributesOf($fresh, static::attributeKeysFor($model), static::excludedFor($model)),
            'relations' => [],
        ];

        foreach (static::relationsFor($model) as $relation) {
            $related = $fresh->{$relation}()->first();
            $snapshot['relations'][$relation] = $related
                ? static::attributesOf($related, null, static::relationKeys($fresh->{$relation}()))
                : null;
        }

        return $snapshot;
    }

    public static function hash(array $snapshot): string
    {
        return md5(json_encode(static::normalize($snapshot), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Schrijft de opname terug, met gewone Eloquent-saves zodat elke hook
     * (cache, url-geschiedenis, zoekindex) meeloopt. De relaties worden op het
     * model gezet, zodat wie daarna `$model->metadata` leest de herstelde
     * versie krijgt, ook binnen een transactie die straks wordt teruggedraaid.
     */
    public static function apply(Model $model, array $snapshot): void
    {
        $attributes = static::onlyExistingColumns($model, (array) ($snapshot['attributes'] ?? []));
        $attributes = array_diff_key($attributes, array_flip(static::excludedFor($model)));

        if ($attributes !== []) {
            $model->setRawAttributes(array_merge($model->getAttributes(), $attributes));
            $model->save();
        }

        foreach (static::relationsFor($model) as $relation) {
            $data = $snapshot['relations'][$relation] ?? null;

            if (! is_array($data)) {
                continue;
            }

            $query = $model->{$relation}();
            $related = $query->first() ?? $query->make();
            $data = array_diff_key(
                static::onlyExistingColumns($related, $data),
                array_flip(static::relationKeys($query)),
            );

            $related->setRawAttributes(array_merge($related->getAttributes(), $data));
            $related->save();
            $model->setRelation($relation, $related);
        }
    }

    /**
     * @return array<int, string>
     */
    protected static function excludedFor(Model $model): array
    {
        $extra = property_exists($model, 'revisionExcept') ? (array) $model->revisionExcept : [];

        return array_values(array_unique(array_merge(static::EXCLUDED, [$model->getKeyName()], $extra)));
    }

    /**
     * @return array<int, string>
     */
    protected static function relationKeys(MorphOneOrMany $relation): array
    {
        $related = $relation->getRelated();

        return array_merge(static::EXCLUDED, [
            $related->getKeyName(),
            $relation->getForeignKeyName(),
            $relation->getMorphType(),
        ]);
    }

    protected static function attributesOf(Model $model, ?array $only, array $except): array
    {
        $attributes = $model->getAttributes();

        if ($only !== null) {
            $attributes = array_intersect_key($attributes, array_flip($only));
        }

        $attributes = array_diff_key($attributes, array_flip($except));
        ksort($attributes);

        return $attributes;
    }

    protected static function onlyExistingColumns(Model $model, array $attributes): array
    {
        $table = $model->getTable();
        static::$columns[$table] ??= Schema::connection($model->getConnectionName())->getColumnListing($table);

        return array_intersect_key($attributes, array_flip(static::$columns[$table]));
    }

    /**
     * MySQL en SQLite geven een getal soms als string en soms als int terug;
     * voor de hash telt alleen de waarde.
     */
    protected static function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);

            return array_map(fn ($item) => static::normalize($item), $value);
        }

        // Een vertaalbare kolom is een JSON-string; dezelfde inhoud kan met
        // de talen in een andere volgorde terugkomen.
        if (is_string($value) && $value !== '' && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return static::normalize($decoded);
            }
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) (is_bool($value) ? (int) $value : $value);
        }

        return $value;
    }

    public static function flushColumnCache(): void
    {
        static::$columns = [];
    }
}

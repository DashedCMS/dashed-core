<?php

namespace Dashed\DashedCore\Classes\Changes;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eén veld dat je kunt inplannen (ScheduledChange) of in bulk bewerken
 * (BulkEditRun). Een pakket meldt zijn velden per model aan via
 * ChangeFields::register(). Het veld weet hoe het gelezen, berekend,
 * geschreven en vergeleken wordt; de rest van het systeem is generiek.
 *
 * Een bewerking is altijd ['op' => ..., 'value' => ...]. Nieuwe waarden
 * worden berekend uit de waarden van vóór de wijziging, zodat "Vorige prijs
 * overnemen van Prijs" en "Prijs -20%" in dezelfde wijziging allebei van de
 * oude prijs uitgaan.
 */
class ChangeField
{
    public const NUMBER = 'number';
    public const INTEGER = 'integer';
    public const BOOLEAN = 'boolean';
    public const TEXT = 'text';
    public const SELECT = 'select';
    public const RELATION = 'relation';
    public const CONTENT = 'content';

    public const OP_SET = 'set';
    public const OP_ADD = 'add';
    public const OP_PERCENT = 'percent';
    public const OP_FROM_FIELD = 'from_field';
    public const OP_CLEAR = 'clear';
    public const OP_ATTACH = 'attach';
    public const OP_DETACH = 'detach';
    public const OP_COPY_FROM = 'copy_from';

    protected string|Closure|null $label = null;

    protected string $type = self::TEXT;

    protected bool $nullable = false;

    protected bool $translatable = false;

    protected array|Closure $options = [];

    protected ?string $relation = null;

    protected bool $schedulable = true;

    protected bool $bulk = true;

    protected ?float $minimum = null;

    protected string|Closure|null $helperText = null;

    public function __construct(protected string $key)
    {
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function helperText(string|Closure|null $text): static
    {
        $this->helperText = $text;

        return $this;
    }

    public function number(?float $minimum = null): static
    {
        $this->type = self::NUMBER;
        $this->minimum = $minimum;

        return $this;
    }

    public function integer(): static
    {
        $this->type = self::INTEGER;

        return $this;
    }

    public function boolean(): static
    {
        $this->type = self::BOOLEAN;

        return $this;
    }

    public function text(): static
    {
        $this->type = self::TEXT;

        return $this;
    }

    public function select(array|Closure $options): static
    {
        $this->type = self::SELECT;
        $this->options = $options;

        return $this;
    }

    /**
     * Een BelongsToMany-relatie (categorieën, verzendklassen). Schrijven is
     * een sync, dus terugzetten zet precies de oude koppelingen terug.
     */
    public function relation(string $relation, array|Closure $options): static
    {
        $this->type = self::RELATION;
        $this->relation = $relation;
        $this->options = $options;

        return $this;
    }

    /**
     * De inhoud (page builder) van een record, overgenomen van een ander
     * record van hetzelfde model op het moment van uitvoeren. Zo kun je een
     * verborgen conceptpagina voorbereiden en die op een tijdstip live zetten.
     */
    public function content(): static
    {
        $this->type = self::CONTENT;
        $this->translatable = true;
        $this->bulk = false;

        return $this;
    }

    public function translatable(bool $translatable = true): static
    {
        $this->translatable = $translatable;

        return $this;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function onlyBulk(): static
    {
        $this->schedulable = false;
        $this->bulk = true;

        return $this;
    }

    public function onlyScheduled(): static
    {
        $this->schedulable = true;
        $this->bulk = false;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        $label = $this->label instanceof Closure ? ($this->label)() : $this->label;

        return (string) ($label ?? $this->key);
    }

    public function getHelperText(): ?string
    {
        return $this->helperText instanceof Closure ? ($this->helperText)() : $this->helperText;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function isSchedulable(): bool
    {
        return $this->schedulable;
    }

    public function isBulkEditable(): bool
    {
        return $this->bulk;
    }

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    /**
     * @return array<string|int, string>
     */
    public function getOptions(): array
    {
        return (array) ($this->options instanceof Closure ? ($this->options)() : $this->options);
    }

    /**
     * @param  array<int, ChangeField>  $siblings  voor "overnemen van"
     * @return array<string, string>
     */
    public function operations(array $siblings = []): array
    {
        $ops = match ($this->type) {
            self::NUMBER => [
                self::OP_SET => __('Vaste waarde'),
                self::OP_ADD => __('Bedrag erbij of eraf'),
                self::OP_PERCENT => __('Percentage erbij of eraf'),
            ],
            self::INTEGER => [
                self::OP_SET => __('Vaste waarde'),
                self::OP_ADD => __('Aantal erbij of eraf'),
            ],
            self::RELATION => [
                self::OP_ATTACH => __('Toevoegen'),
                self::OP_DETACH => __('Verwijderen'),
                self::OP_SET => __('Vervangen door'),
            ],
            self::CONTENT => [
                self::OP_COPY_FROM => __('Overnemen van'),
            ],
            default => [
                self::OP_SET => __('Vaste waarde'),
            ],
        };

        // "Overnemen van Prijs" is per bronveld een eigen keuze
        // (from_field:price), zodat er geen tweede invoerveld nodig is.
        if ($this->type === self::NUMBER) {
            foreach ($siblings as $sibling) {
                if ($sibling !== $this && $sibling->getType() === self::NUMBER) {
                    $ops[self::OP_FROM_FIELD . ':' . $sibling->getKey()] = __('Overnemen van :veld', ['veld' => $sibling->getLabel()]);
                }
            }
        }

        if ($this->nullable && in_array($this->type, [self::NUMBER, self::TEXT, self::SELECT], true)) {
            $ops[self::OP_CLEAR] = __('Leegmaken');
        }

        return $ops;
    }

    public function read(Model $model, ?string $locale = null): mixed
    {
        if ($this->type === self::RELATION) {
            $relation = $model->{$this->relation}();

            $ids = $relation instanceof BelongsToMany
                ? $relation->allRelatedIds()->all()
                : $relation->pluck($relation->getRelated()->getKeyName())->all();

            return static::sortedIds($ids);
        }

        if ($this->translatable && method_exists($model, 'getTranslation')) {
            return $model->getTranslation($this->key, $locale ?: app()->getLocale(), false);
        }

        $attributes = $model->getAttributes();
        $value = $attributes[$this->key] ?? null;

        return match ($this->type) {
            self::NUMBER => $value === null || $value === '' ? null : round((float) $value, 2),
            self::INTEGER => $value === null || $value === '' ? null : (int) $value,
            self::BOOLEAN => (bool) $value,
            default => $value,
        };
    }

    /**
     * Rekent de nieuwe waarde uit. $originals bevat de waarden van vóór de
     * wijziging van alle betrokken velden (voor "overnemen van").
     *
     * @param  array<string, mixed>  $originals
     */
    public function compute(array $change, mixed $current, array $originals, Model $model, ?string $locale = null): mixed
    {
        $op = $change['op'] ?? self::OP_SET;
        $value = $change['value'] ?? null;

        $new = match ($this->type) {
            self::NUMBER => match ($op) {
                self::OP_SET => $value === null || $value === '' ? null : (float) $value,
                self::OP_ADD => (float) ($current ?? 0) + (float) $value,
                self::OP_PERCENT => $current === null ? null : (float) $current * (1 + ((float) $value / 100)),
                self::OP_FROM_FIELD => array_key_exists((string) $value, $originals) ? $originals[$value] : $current,
                self::OP_CLEAR => null,
                default => $current,
            },
            self::INTEGER => match ($op) {
                self::OP_ADD => (int) ($current ?? 0) + (int) $value,
                default => $value === null || $value === '' ? null : (int) $value,
            },
            self::BOOLEAN => (bool) $value,
            self::RELATION => match ($op) {
                self::OP_ATTACH => static::sortedIds(array_merge((array) $current, (array) $value)),
                self::OP_DETACH => static::sortedIds(array_diff((array) $current, static::sortedIds((array) $value))),
                default => static::sortedIds((array) $value),
            },
            self::CONTENT => $this->contentFrom($model, $value, $locale),
            default => $op === self::OP_CLEAR ? null : $value,
        };

        if ($this->type === self::NUMBER && $new !== null) {
            $new = round((float) $new, 2);

            if ($this->minimum !== null && $new < $this->minimum) {
                $new = $this->minimum;
            }
        }

        if ($this->type === self::INTEGER && $new === null && ! $this->nullable) {
            $new = 0;
        }

        return $new;
    }

    public function write(Model $model, mixed $value, ?string $locale = null): void
    {
        if ($this->type === self::RELATION) {
            $model->{$this->relation}()->sync(static::sortedIds((array) $value));

            return;
        }

        if ($this->translatable && method_exists($model, 'setTranslation')) {
            $model->setTranslation($this->key, $locale ?: app()->getLocale(), $value);

            return;
        }

        $model->setAttribute($this->key, $value);
    }

    public function same(mixed $a, mixed $b): bool
    {
        return match ($this->type) {
            self::NUMBER => ($a === null || $a === '' || $b === null || $b === '')
                ? (($a === null || $a === '') === ($b === null || $b === ''))
                : round((float) $a, 2) === round((float) $b, 2),
            self::INTEGER => $this->nullable && (($a === null) !== ($b === null)) ? false : (int) $a === (int) $b,
            self::BOOLEAN => (bool) $a === (bool) $b,
            self::RELATION => static::sortedIds((array) $a) === static::sortedIds((array) $b),
            default => json_encode($a) === json_encode($b),
        };
    }

    /**
     * Leesbare weergave voor het voorbeeld en het overzicht.
     */
    public function display(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '-';
        }

        return match ($this->type) {
            self::NUMBER => number_format((float) $value, 2, ',', '.'),
            self::BOOLEAN => $value ? __('Ja') : __('Nee'),
            self::SELECT => (string) ($this->getOptions()[$value] ?? $value),
            self::RELATION => collect((array) $value)->map(fn ($id) => $this->getOptions()[$id] ?? ('#' . $id))->implode(', '),
            self::CONTENT => trans_choice(':count blok|:count blokken', is_array($value) ? count($value) : 0),
            default => is_array($value) ? json_encode($value) : (string) $value,
        };
    }

    /**
     * Leesbare omschrijving van een bewerking, voor het overzicht van
     * geplande wijzigingen en bulkruns.
     *
     * @param  array<string, ChangeField>  $siblings
     */
    public function describe(array $change, array $siblings = []): string
    {
        $op = $change['op'] ?? self::OP_SET;
        $value = $change['value'] ?? null;

        $text = match ($op) {
            self::OP_ADD => ((float) $value >= 0 ? '+' : '') . ($this->type === self::NUMBER ? number_format((float) $value, 2, ',', '.') : (int) $value),
            self::OP_PERCENT => ((float) $value >= 0 ? '+' : '') . rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',') . '%',
            self::OP_FROM_FIELD => __('overnemen van :veld', ['veld' => isset($siblings[$value]) ? $siblings[$value]->getLabel() : $value]),
            self::OP_CLEAR => __('leegmaken'),
            self::OP_ATTACH => __('toevoegen: :waarde', ['waarde' => $this->display($value)]),
            self::OP_DETACH => __('verwijderen: :waarde', ['waarde' => $this->display($value)]),
            self::OP_COPY_FROM => __('overnemen van #:id', ['id' => $value]),
            default => $this->display($value),
        };

        return $this->getLabel() . ': ' . $text;
    }

    protected function contentFrom(Model $model, mixed $sourceId, ?string $locale): mixed
    {
        $source = $model->newQuery()->find($sourceId);

        if (! $source) {
            throw new \RuntimeException(__('Het record waarvan de inhoud overgenomen wordt (#:id) bestaat niet meer.', ['id' => $sourceId]));
        }

        return method_exists($source, 'getTranslation')
            ? $source->getTranslation($this->key, $locale ?: app()->getLocale(), false)
            : $source->getAttribute($this->key);
    }

    /**
     * @return array<int, int|string>
     */
    public static function sortedIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map(fn ($id) => is_numeric($id) ? (int) $id : $id, array_filter($ids, fn ($id) => $id !== null && $id !== ''))));
        sort($ids);

        return $ids;
    }
}

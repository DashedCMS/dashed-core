<?php

namespace Dashed\DashedCore\Classes\Changes;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Het gedeelde hart van ingeplande wijzigingen en bulkbewerkingen: het
 * formulier om bewerkingen te kiezen, en het uitrekenen en wegschrijven
 * ervan op één record.
 */
class ChangeSet
{
    public const SKIP = 'skip';

    /**
     * Per veld een keuze "Niet wijzigen / Vaste waarde / ..." met het
     * bijbehorende invoerveld. Sleutels: op_<veld> en value_<veld>.
     *
     * @param  array<string, ChangeField>  $fields
     * @return array<int, mixed>
     */
    public static function schema(array $fields, string $modelClass, ?Model $record = null): array
    {
        $components = [];

        foreach ($fields as $key => $field) {
            $opKey = 'op_' . $key;
            $valueKey = 'value_' . $key;
            $ops = $field->operations($fields);

            $components[] = Grid::make(2)->schema([
                Select::make($opKey)
                    ->label($field->getLabel())
                    ->helperText($field->getHelperText())
                    ->options([self::SKIP => __('Niet wijzigen')] + $ops)
                    ->default(self::SKIP)
                    ->selectablePlaceholder(false)
                    ->live(),
                static::valueComponent($field, $valueKey, $fields, $modelClass, $record)
                    ->hidden(fn (Get $get) => ! static::needsValue((string) ($get($opKey) ?? self::SKIP)))
                    ->required(fn (Get $get) => static::needsValue((string) ($get($opKey) ?? self::SKIP)) && $field->getType() !== ChangeField::BOOLEAN),
            ]);
        }

        return $components;
    }

    /**
     * Haalt de gekozen bewerkingen uit de formulierdata.
     *
     * @param  array<string, ChangeField>  $fields
     * @return array<string, array{op: string, value: mixed}>
     */
    public static function fromForm(array $fields, array $data): array
    {
        $changes = [];

        foreach ($fields as $key => $field) {
            $op = $data['op_' . $key] ?? self::SKIP;

            if ($op === null || $op === self::SKIP || ! array_key_exists($op, $field->operations($fields))) {
                continue;
            }

            if (str_starts_with($op, ChangeField::OP_FROM_FIELD . ':')) {
                $changes[$key] = ['op' => ChangeField::OP_FROM_FIELD, 'value' => substr($op, strlen(ChangeField::OP_FROM_FIELD) + 1)];

                continue;
            }

            $value = $data['value_' . $key] ?? null;

            if ($op !== ChangeField::OP_CLEAR && ($value === null || $value === '' || $value === []) && $field->getType() !== ChangeField::BOOLEAN) {
                continue;
            }

            if ($field->getType() === ChangeField::BOOLEAN) {
                $value = (bool) $value;
            }

            $changes[$key] = ['op' => $op, 'value' => $op === ChangeField::OP_CLEAR ? null : $value];
        }

        return $changes;
    }

    /**
     * Rekent per veld de oude en nieuwe waarde uit. Velden waar niets
     * verandert vallen weg.
     *
     * @param  array<string, ChangeField>  $fields
     * @param  array<string, array{op: string, value: mixed}>  $changes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function plan(Model $model, array $fields, array $changes, ?string $locale = null): array
    {
        $originals = [];

        foreach ($fields as $key => $field) {
            if (isset($changes[$key]) || static::isSourceOf($key, $changes)) {
                $originals[$key] = $field->read($model, $locale);
            }
        }

        $plan = [];

        foreach ($changes as $key => $change) {
            $field = $fields[$key] ?? null;

            if (! $field) {
                continue;
            }

            $new = $field->compute($change, $originals[$key] ?? null, $originals, $model, $locale);

            if (! $field->same($originals[$key] ?? null, $new)) {
                $plan[$key] = ['old' => $originals[$key] ?? null, 'new' => $new];
            }
        }

        return $plan;
    }

    /**
     * Schrijft waarden weg via de gewone Eloquent-save, zodat events, feeds
     * en caches meelopen. Relaties eerst, zodat de saved-hook (bijvoorbeeld
     * de productinformatie-job) de nieuwe koppelingen al ziet.
     *
     * @param  array<string, ChangeField>  $fields
     * @param  array<string, mixed>  $values
     */
    public static function write(Model $model, array $fields, array $values, ?string $locale = null): void
    {
        foreach ($values as $key => $value) {
            if (($fields[$key] ?? null)?->getType() === ChangeField::RELATION) {
                $fields[$key]->write($model, $value, $locale);
            }
        }

        foreach ($values as $key => $value) {
            $field = $fields[$key] ?? null;

            if ($field && $field->getType() !== ChangeField::RELATION) {
                $field->write($model, $value, $locale);
            }
        }

        // Ook zonder gewijzigde kolom (alleen een relatie) vuurt save() de
        // saved-hook af.
        $model->save();
    }

    /**
     * Wat er terug kan: per veld de oude waarde, alleen als het veld nog
     * staat op wat deze wijziging er neerzette. Een veld dat iemand sindsdien
     * zelf aanpaste, blijft staan en komt in $skipped.
     *
     * @param  array<string, ChangeField>  $fields
     * @param  array<string, mixed>  $applied
     * @param  array<string, mixed>  $original
     * @return array{restore: array<string, mixed>, skipped: array<int, string>}
     */
    public static function restorable(Model $model, array $fields, array $applied, array $original, ?string $locale = null): array
    {
        $restore = [];
        $skipped = [];

        foreach ($applied as $key => $value) {
            $field = $fields[$key] ?? null;

            if (! $field || ! array_key_exists($key, $original)) {
                $skipped[] = $key;

                continue;
            }

            if ($field->same($field->read($model, $locale), $value)) {
                $restore[$key] = $original[$key];
            } else {
                $skipped[] = $key;
            }
        }

        return ['restore' => $restore, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, ChangeField>  $fields
     * @param  array<string, array{op: string, value: mixed}>  $changes
     */
    public static function describe(array $fields, array $changes): string
    {
        return collect($changes)
            ->map(fn (array $change, string $key) => isset($fields[$key]) ? $fields[$key]->describe($change, $fields) : $key)
            ->implode('; ');
    }

    /**
     * @param  array<string, array{op: string, value: mixed}>  $changes
     */
    protected static function isSourceOf(string $key, array $changes): bool
    {
        foreach ($changes as $change) {
            if (($change['op'] ?? null) === ChangeField::OP_FROM_FIELD && ($change['value'] ?? null) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, ChangeField>  $fields
     */
    protected static function valueComponent(ChangeField $field, string $valueKey, array $fields, string $modelClass, ?Model $record): Select|TextInput
    {
        $opKey = 'op_' . $field->getKey();

        return match ($field->getType()) {
            ChangeField::NUMBER => TextInput::make($valueKey)
                ->label(fn (Get $get) => match ($get($opKey)) {
                    ChangeField::OP_ADD => __('Bedrag (negatief = eraf)'),
                    ChangeField::OP_PERCENT => __('Percentage (negatief = eraf)'),
                    default => __('Nieuwe waarde'),
                })
                ->numeric(),
            ChangeField::INTEGER => TextInput::make($valueKey)
                ->label(fn (Get $get) => $get($opKey) === ChangeField::OP_ADD ? __('Aantal (negatief = eraf)') : __('Nieuwe waarde'))
                ->integer(),
            ChangeField::BOOLEAN => Select::make($valueKey)
                ->label(__('Nieuwe waarde'))
                ->options([1 => __('Ja'), 0 => __('Nee')])
                ->default(1)
                ->selectablePlaceholder(false),
            ChangeField::SELECT => Select::make($valueKey)
                ->label(__('Nieuwe waarde'))
                ->options(fn () => $field->getOptions()),
            ChangeField::RELATION => Select::make($valueKey)
                ->label(__('Waarden'))
                ->multiple()
                ->searchable()
                ->options(fn () => $field->getOptions()),
            ChangeField::CONTENT => Select::make($valueKey)
                ->label(__('Inhoud overnemen van'))
                ->searchable()
                ->options(fn () => $modelClass::query()
                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                    ->limit(500)
                    ->get()
                    ->mapWithKeys(fn (Model $model) => [$model->getKey() => ChangeFields::recordLabel($model)])
                    ->all()),
            default => TextInput::make($valueKey)->label(__('Nieuwe waarde')),
        };
    }

    protected static function needsValue(string $op): bool
    {
        return ! in_array($op, [self::SKIP, ChangeField::OP_CLEAR], true)
            && ! str_starts_with($op, ChangeField::OP_FROM_FIELD . ':');
    }
}

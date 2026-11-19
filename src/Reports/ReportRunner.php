<?php

namespace Dashed\DashedCore\Reports;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Classes\Reports\SqlPeriod;

/**
 * Voert een rapportdefinitie uit als één aggregaatquery (plus één voor de
 * totaalregel). Elke sleutel uit de definitie wordt opgezocht in de bron;
 * een onbekende meetwaarde, groepering of filter valt weg in plaats van in
 * de query te belanden, en filterwaarden gaan als gebonden parameters mee.
 */
class ReportRunner
{
    public const MAX_ROWS = 1000;

    /**
     * @param  array{source: string, measures?: array<int, string>, grouping?: ?string, filters?: array<string, array<int, mixed>>, period?: ?string, date_from?: mixed, date_to?: mixed}  $definition
     */
    public static function run(array $definition): ReportResult
    {
        $source = ReportRegistry::get($definition['source'] ?? null);
        if (! $source) {
            throw new InvalidArgumentException(__('Onbekende bron voor dit rapport.'));
        }

        $allMeasures = $source->measures();
        $measures = array_values(array_intersect((array) ($definition['measures'] ?? []), array_keys($allMeasures)));
        if ($measures === []) {
            $measures = [array_key_first($allMeasures)];
        }

        $groupings = $source->groupings();
        $groupingKey = $definition['grouping'] ?? null;
        $grouping = $groupingKey !== null ? ($groupings[$groupingKey] ?? null) : null;

        [$from, $to] = ReportPeriod::range($definition['period'] ?? null, $definition['date_from'] ?? null, $definition['date_to'] ?? null);

        $base = $source->query()->whereBetween($source->dateColumn(), [$from, $to]);
        $joins = [];
        foreach ($measures as $key) {
            $joins = [...$joins, ...$allMeasures[$key]->joins];
        }
        if ($grouping) {
            $joins = [...$joins, ...$grouping->joins];
        }

        $filters = $source->filters();
        foreach ((array) ($definition['filters'] ?? []) as $key => $values) {
            $filter = $filters[$key] ?? null;
            $values = array_values(array_filter((array) $values, fn ($value) => $value !== null && $value !== ''));
            if (! $filter || $values === []) {
                continue;
            }
            $allowed = array_map('strval', array_keys(($filter->options)()));
            $values = array_values(array_filter($values, fn ($value) => in_array((string) $value, $allowed, true)));
            if ($values === []) {
                continue;
            }
            $joins = [...$joins, ...$filter->joins];
            $base->whereIn($filter->column, $values);
        }

        self::applyJoins($base, $source, array_unique($joins));

        $selects = [];
        foreach ($measures as $key) {
            $selects[] = $allMeasures[$key]->sql.' as '.self::alias($key);
        }

        $totals = (clone $base)->toBase()->selectRaw(implode(', ', $selects))->first();

        $rows = [];
        $truncated = false;
        if ($grouping) {
            $expression = $grouping->period
                ? SqlPeriod::expression($source->dateColumn(), $grouping->period)
                : self::column($grouping->column);

            $query = (clone $base)->toBase()
                ->selectRaw($expression.' as report_group, '.implode(', ', $selects))
                ->groupByRaw($expression);

            $grouping->period
                ? $query->orderByRaw($expression)
                : $query->orderByDesc(self::alias($measures[0]));

            $raw = $query->limit(self::MAX_ROWS + 1)->get();
            $truncated = $raw->count() > self::MAX_ROWS;
            $raw = $raw->take(self::MAX_ROWS);

            $labels = $grouping->labels
                ? (array) ($grouping->labels)($raw->pluck('report_group')->filter(fn ($value) => $value !== null)->unique()->values()->all())
                : [];

            foreach ($raw as $row) {
                $group = $row->report_group;
                $line = [
                    'key' => $group,
                    'group' => $grouping->period
                        ? SqlPeriod::label($group === null ? null : (string) $group, $grouping->period)
                        : ($group === null || $group === '' ? __('(onbekend)') : (string) ($labels[$group] ?? $group)),
                ];
                foreach ($measures as $key) {
                    $line[$key] = self::number($row->{self::alias($key)} ?? null);
                }
                $rows[] = $line;
            }
        }

        $columns = [];
        $totalLine = [];
        foreach ($measures as $key) {
            $columns[$key] = ['label' => $allMeasures[$key]->label, 'format' => $allMeasures[$key]->format];
            $totalLine[$key] = self::number($totals->{self::alias($key)} ?? null);
        }

        return new ReportResult(
            $grouping?->label ?? __('Periode'),
            $columns,
            $rows,
            $totalLine,
            $from,
            $to,
            $truncated,
        );
    }

    protected static function applyJoins(Builder $query, ReportSource $source, array $names): void
    {
        $available = $source->joins();
        foreach ($names as $name) {
            if (isset($available[$name])) {
                ($available[$name])($query);
            }
        }
    }

    protected static function alias(string $key): string
    {
        return 'm_'.preg_replace('/[^a-z0-9_]/', '_', strtolower($key));
    }

    protected static function column(?string $column): string
    {
        if (! $column || ! preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $column)) {
            throw new InvalidArgumentException("Invalid grouping column [{$column}]");
        }

        return $column;
    }

    protected static function number(mixed $value): float|int|null
    {
        if ($value === null) {
            return null;
        }

        return is_numeric($value) && (string) (int) $value === (string) $value ? (int) $value : (float) $value;
    }
}

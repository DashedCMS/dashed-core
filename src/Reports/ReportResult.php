<?php

namespace Dashed\DashedCore\Reports;

use Illuminate\Support\Carbon;

/**
 * De uitkomst van een rapport: kolommen, rijen (elk met een `group`-label en
 * per meetwaarde een getal) en een totaalregel over de hele periode.
 */
class ReportResult
{
    /**
     * @param  array<string, array{label: string, format: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, float|int|null>  $totals
     */
    public function __construct(
        public string $groupLabel,
        public array $columns,
        public array $rows,
        public array $totals,
        public Carbon $from,
        public Carbon $to,
        public bool $truncated = false,
    ) {
    }

    public static function format(float|int|null $value, string $format): string
    {
        if ($value === null) {
            return '-';
        }

        return match ($format) {
            'money' => '€ '.number_format((float) $value, 2, ',', '.'),
            'decimal' => number_format((float) $value, 2, ',', '.'),
            default => number_format((float) $value, 0, ',', '.'),
        };
    }

    /**
     * Kale tabel voor export: kopregel, rijen, totaalregel.
     *
     * @return array<int, array<int, mixed>>
     */
    public function toArray(): array
    {
        $table = [[$this->groupLabel, ...array_column($this->columns, 'label')]];

        foreach ($this->rows as $row) {
            $line = [$row['group']];
            foreach (array_keys($this->columns) as $key) {
                $line[] = $row[$key] === null ? null : round((float) $row[$key], 2);
            }
            $table[] = $line;
        }

        $total = [__('Totaal')];
        foreach (array_keys($this->columns) as $key) {
            $total[] = $this->totals[$key] === null ? null : round((float) $this->totals[$key], 2);
        }
        $table[] = $total;

        return $table;
    }
}

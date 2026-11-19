<?php

namespace Dashed\DashedCore\Reports;

use Closure;

/**
 * Een groepering van een rapportbron. Een periodegroepering (dag, week,
 * maand) zet `period` en werkt op de datumkolom van de bron; een gewone
 * groepering geeft een kolom en eventueel een functie die de gevonden
 * sleutels naar namen vertaalt (een betaalmethode-id naar zijn naam), in één
 * query voor alle sleutels samen.
 */
class ReportGrouping
{
    /**
     * @param  ?Closure(array<int, mixed>): array<string|int, string>  $labels
     * @param  array<int, string>  $joins
     */
    public function __construct(
        public string $label,
        public ?string $column = null,
        public ?string $period = null,
        public ?Closure $labels = null,
        public array $joins = [],
    ) {
    }

    public static function period(string $label, string $unit): self
    {
        return new self($label, period: $unit);
    }

    public static function column(string $label, string $column, ?Closure $labels = null, array $joins = []): self
    {
        return new self($label, $column, null, $labels, $joins);
    }
}

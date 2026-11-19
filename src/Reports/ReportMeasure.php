<?php

namespace Dashed\DashedCore\Reports;

/**
 * Een meetwaarde van een rapportbron: een aggregaat in SQL. De expressie
 * schrijft de bron zelf, nooit de beheerder; de beheerder kiest alleen een
 * sleutel uit de lijst.
 */
class ReportMeasure
{
    /**
     * @param  string  $sql  Aggregaat, bijvoorbeeld `SUM(dashed__orders.total)`.
     * @param  string  $format  `number`, `money` of `decimal`.
     * @param  array<int, string>  $joins  Sleutels uit ReportSource::joins() die deze meetwaarde nodig heeft.
     */
    public function __construct(
        public string $label,
        public string $sql,
        public string $format = 'number',
        public array $joins = [],
    ) {
    }

    public static function make(string $label, string $sql, string $format = 'number', array $joins = []): self
    {
        return new self($label, $sql, $format, $joins);
    }
}

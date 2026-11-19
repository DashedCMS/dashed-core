<?php

namespace Dashed\DashedCore\Reports;

use Closure;

/**
 * Een filter van een rapportbron: een keuzelijst met waarden, toegepast op
 * één kolom met whereIn. Alleen waarden uit de keuzelijst tellen, zodat een
 * opgeslagen rapport niet meer kan dan het scherm toonde.
 */
class ReportFilter
{
    /**
     * @param  Closure(): array<string|int, string>  $options
     * @param  array<int, string>  $joins
     */
    public function __construct(
        public string $label,
        public string $column,
        public Closure $options,
        public array $joins = [],
    ) {
    }

    public static function make(string $label, string $column, Closure $options, array $joins = []): self
    {
        return new self($label, $column, $options, $joins);
    }
}

<?php

namespace Dashed\DashedCore\Services\Summary\DTOs;

use Carbon\Carbon;

/**
 * Immutable periode-DTO die door de scheduler aan elke contributor
 * wordt meegegeven. Bevat de exacte start- en eind-tijdstempel zodat
 * contributors hun queries op een geïndexeerde whereBetween kunnen
 * uitvoeren in plaats van whereDate.
 */
class SummaryPeriod
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $frequency,
        public readonly string $label,
    ) {
    }
}

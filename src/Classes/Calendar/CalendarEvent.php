<?php

namespace Dashed\DashedCore\Classes\Calendar;

use Illuminate\Support\Carbon;

/**
 * Eén regel op de campagnekalender. `overlapKeys` zegt waarmee hij niet
 * tegelijk hoort te lopen: twee events met een gedeelde sleutel en een
 * overlappende periode zijn een conflict. Een sleutel die op `*` eindigt
 * (`popup:*`, een popup op alle pagina's) botst met elke sleutel met
 * hetzelfde voorvoegsel.
 */
class CalendarEvent
{
    /**
     * @param  array<int, string>  $overlapKeys
     */
    public function __construct(
        public string $source,
        public string $title,
        public Carbon $start,
        public ?Carbon $end = null,
        public ?string $url = null,
        public array $overlapKeys = [],
    ) {
    }

    public function endOr(Carbon $fallback): Carbon
    {
        return $this->end ?? $fallback;
    }

    public function overlapsInTime(self $other, Carbon $horizon): bool
    {
        return $this->start->lte($other->endOr($horizon)) && $other->start->lte($this->endOr($horizon));
    }

    public function sharesKeyWith(self $other): ?string
    {
        foreach ($this->overlapKeys as $mine) {
            foreach ($other->overlapKeys as $theirs) {
                if ($mine === $theirs || self::wildcardMatch($mine, $theirs) || self::wildcardMatch($theirs, $mine)) {
                    return str_ends_with($mine, '*') ? $theirs : $mine;
                }
            }
        }

        return null;
    }

    protected static function wildcardMatch(string $pattern, string $key): bool
    {
        return str_ends_with($pattern, '*') && str_starts_with($key, substr($pattern, 0, -1));
    }
}

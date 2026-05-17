<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Services\Summary;

use Carbon\Carbon;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;

/**
 * Bouwt SummaryPeriod-instanties op basis van een frequency-string.
 * Wordt gebruikt door zowel de scheduler (DispatchSummaryMailsCommand)
 * als door ad-hoc test-acties in de admin.
 */
final class SummaryPeriodFactory
{
    public static function make(string $frequency): ?SummaryPeriod
    {
        return match ($frequency) {
            'daily' => new SummaryPeriod(
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
                'daily',
                'Gisteren',
            ),
            'weekly' => new SummaryPeriod(
                Carbon::now()->subDays(7)->startOfDay(),
                Carbon::now()->subDay()->endOfDay(),
                'weekly',
                'Afgelopen 7 dagen',
            ),
            'monthly' => new SummaryPeriod(
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
                'monthly',
                Carbon::now()->subMonthNoOverflow()->translatedFormat('F Y'),
            ),
            default => null,
        };
    }
}

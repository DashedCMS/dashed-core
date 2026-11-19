<?php

namespace Dashed\DashedCore\Reports;

use Illuminate\Support\Carbon;

/**
 * De periodes waaruit een rapport kiest. Een ingepland rapport rekent zijn
 * periode uit op het moment van versturen, dus "vorige maand" is in de mail
 * van 1 maart de hele februari.
 */
class ReportPeriod
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'today' => __('Vandaag'),
            'yesterday' => __('Gisteren'),
            'last_7_days' => __('Laatste 7 dagen'),
            'last_30_days' => __('Laatste 30 dagen'),
            'last_90_days' => __('Laatste 90 dagen'),
            'this_week' => __('Deze week'),
            'last_week' => __('Vorige week'),
            'this_month' => __('Deze maand'),
            'last_month' => __('Vorige maand'),
            'this_quarter' => __('Dit kwartaal'),
            'this_year' => __('Dit jaar'),
            'last_year' => __('Vorig jaar'),
            'custom' => __('Eigen periode'),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function range(?string $period, $from = null, $to = null, ?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_90_days' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->subDays(29)->startOfDay(),
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}

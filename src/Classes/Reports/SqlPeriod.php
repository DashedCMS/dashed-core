<?php

namespace Dashed\DashedCore\Classes\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Een datumkolom als periodesleutel in SQL (dag, week, maand, jaar), zodat
 * een rapport of widget in de database groepeert in plaats van rijen in PHP
 * over stappen te verdelen. MySQL en SQLite (tests) hebben elk hun eigen
 * functie; de sleutel is in beide gelijk van vorm, zodat de labels kloppen.
 *
 * Week is de ISO-week (`2026-W07`) in MySQL; SQLite kent geen ISO-week en
 * telt vanaf de eerste maandag van het jaar (`%W`). Rond de jaarwisseling
 * kan dat één week schuiven, en dat raakt alleen de tests.
 */
class SqlPeriod
{
    public const UNITS = ['day', 'week', 'month', 'year'];

    public static function expression(string $column, string $unit, ?string $connection = null): string
    {
        if (! in_array($unit, self::UNITS, true)) {
            throw new \InvalidArgumentException("Unknown period unit [{$unit}]");
        }

        $driver = DB::connection($connection)->getDriverName();
        $column = self::wrap($column);

        if ($driver === 'sqlite') {
            return match ($unit) {
                'day' => "strftime('%Y-%m-%d', {$column})",
                'week' => "strftime('%Y', {$column}) || '-W' || strftime('%W', {$column})",
                'month' => "strftime('%Y-%m', {$column})",
                'year' => "strftime('%Y', {$column})",
            };
        }

        if ($driver === 'pgsql') {
            return match ($unit) {
                'day' => "to_char({$column}, 'YYYY-MM-DD')",
                'week' => "to_char({$column}, 'IYYY-\"W\"IW')",
                'month' => "to_char({$column}, 'YYYY-MM')",
                'year' => "to_char({$column}, 'YYYY')",
            };
        }

        return match ($unit) {
            'day' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
            'week' => "DATE_FORMAT({$column}, '%x-W%v')",
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
            'year' => "DATE_FORMAT({$column}, '%Y')",
        };
    }

    /** Leesbaar label voor een sleutel uit expression(). */
    public static function label(?string $key, string $unit): string
    {
        if ($key === null || $key === '') {
            return '-';
        }

        return rescue(fn () => match ($unit) {
            'day' => Carbon::createFromFormat('Y-m-d', $key)->format('d-m-Y'),
            'week' => str_replace('-W', ' week ', $key),
            'month' => Carbon::createFromFormat('Y-m-d', $key.'-01')->translatedFormat('F Y'),
            default => $key,
        }, $key, false);
    }

    /** Alleen kolomnamen als `tabel.kolom` of `kolom`; nooit vrije SQL. */
    protected static function wrap(string $column): string
    {
        if (! preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $column)) {
            throw new \InvalidArgumentException("Invalid column [{$column}]");
        }

        return $column;
    }
}

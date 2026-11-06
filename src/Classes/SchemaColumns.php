<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Schema;

/**
 * Schema::hasColumn() maakt op MySQL/MariaDB per aanroep een tijdelijke tabel
 * op schijf aan. Onthoud daarom per proces dat een kolom bestaat. Alleen een
 * positief antwoord blijft staan, zodat een draaiende worker een net
 * gemigreerde kolom meteen ziet. De waarde leeft in de container en niet in een
 * static, want die overleeft de herstart tussen twee tests.
 */
class SchemaColumns
{
    public static function has(string $table, string $column): bool
    {
        $key = "dashed.schema-columns.{$table}.{$column}";

        if (app()->bound($key)) {
            return true;
        }

        if (! Schema::hasColumn($table, $column)) {
            return false;
        }

        app()->instance($key, true);

        return true;
    }
}

<?php

namespace Dashed\DashedCore\Reports;

use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Een rapport als csv of xlsx. Xlsx gaat via Laravel-Excel (alleen
 * FromArray, zodat het op 3.1 en 4.0 werkt) en is er alleen als dat pakket
 * geïnstalleerd is; dashed-core vereist het zelf niet.
 */
class ReportExporter
{
    public static function xlsxAvailable(): bool
    {
        return class_exists(Excel::class) && interface_exists(FromArray::class);
    }

    /** @return array<string, string> */
    public static function formats(): array
    {
        return self::xlsxAvailable() ? ['xlsx' => 'Excel (xlsx)', 'csv' => 'CSV'] : ['csv' => 'CSV'];
    }

    public static function contents(ReportResult $result, string $format): string
    {
        if ($format === 'xlsx' && self::xlsxAvailable()) {
            $export = new class ($result->toArray()) implements FromArray {
                public function __construct(private array $rows)
                {
                }

                public function array(): array
                {
                    return $this->rows;
                }
            };

            return Excel::raw($export, ExcelType::XLSX);
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        foreach ($result->toArray() as $row) {
            fputcsv($handle, array_map(fn ($value) => is_float($value) ? str_replace('.', ',', (string) $value) : $value, $row), ';');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public static function filename(string $name, string $format): string
    {
        $format = $format === 'xlsx' && self::xlsxAvailable() ? 'xlsx' : 'csv';

        return (\Illuminate\Support\Str::slug($name) ?: 'rapport').'-'.now()->format('Y-m-d').'.'.$format;
    }
}

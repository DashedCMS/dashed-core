<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Builder\Block;

/**
 * Multi-koloms tabel-block, primair gebruikt voor top-N lijsten in de
 * samenvatting-mails (bv. top 5 producten op aantal verkocht).
 *
 * Verwacht in $blockData:
 *   - 'headers' => array<string>: kolom-titels.
 *   - 'rows' => array<array<int, string>>: per rij dezelfde lengte als headers.
 * Strict inline-styles met afwisselende rij-achtergrondkleuren voor
 * leesbaarheid in mail-clients die geen <style>-blokken volledig
 * ondersteunen.
 */
class TableBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'table';
    }

    public static function label(): string
    {
        return 'Tabel';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-table-cells')
            ->schema([
                TagsInput::make('headers')
                    ->label('Kolom-titels')
                    ->required(),
                Repeater::make('rows')
                    ->label('Rijen')
                    ->schema([
                        TagsInput::make('cells')->label('Cellen'),
                    ])
                    ->minItems(1)
                    ->defaultItems(1),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        $headers = [];
        foreach ($blockData['headers'] ?? [] as $header) {
            $headers[] = self::substitute((string) $header, $context);
        }

        $rows = [];
        foreach ($blockData['rows'] ?? [] as $row) {
            if (is_array($row) && array_key_exists('cells', $row) && is_array($row['cells'])) {
                $cells = $row['cells'];
            } elseif (is_array($row)) {
                $cells = $row;
            } else {
                continue;
            }

            $rendered = [];
            foreach ($cells as $cell) {
                $rendered[] = self::substitute((string) $cell, $context);
            }
            $rows[] = $rendered;
        }

        return view('dashed-core::emails.blocks.table', [
            'headers' => $headers,
            'rows' => $rows,
        ])->render();
    }
}

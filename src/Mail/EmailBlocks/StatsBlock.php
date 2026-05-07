<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

/**
 * Stats-block, primair gebruikt door de samenvatting-mails.
 *
 * Verwacht in $blockData een 'rows' array met items van de vorm:
 *   ['label' => 'Orders', 'value' => '12', 'sub' => '€ 1.234,56']
 * Het 'sub'-veld is optioneel en wordt onder de waarde gerenderd in
 * een kleinere font-size, geschikt voor toelichting zoals omzet bij
 * een aantal-orders.
 */
class StatsBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'stats';
    }

    public static function label(): string
    {
        return 'Statistieken';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Repeater::make('rows')
                    ->label('Rijen')
                    ->schema([
                        TextInput::make('label')->label('Label')->required(),
                        TextInput::make('value')->label('Waarde')->required(),
                        TextInput::make('sub')->label('Toelichting')->nullable(),
                    ])
                    ->minItems(1)
                    ->defaultItems(1),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        $rows = [];
        foreach ($blockData['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rows[] = [
                'label' => self::substitute((string) ($row['label'] ?? ''), $context),
                'value' => self::substitute((string) ($row['value'] ?? ''), $context),
                'sub' => self::substitute((string) ($row['sub'] ?? ''), $context),
            ];
        }

        return view('dashed-core::emails.blocks.stats', [
            'rows' => $rows,
        ])->render();
    }
}

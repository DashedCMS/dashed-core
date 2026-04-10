<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Builder\Block;

class OrderSummaryBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'order-summary';
    }

    public static function label(): string
    {
        return 'Order overzicht';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-shopping-bag')
            ->schema([
                Toggle::make('show_totals')->label('Totalen tonen')->default(true),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        $order = $context['order'] ?? null;
        if (! $order) {
            return '';
        }

        return view('dashed-core::emails.blocks.order-summary', [
            'order' => $order,
            'showTotals' => (bool) ($blockData['show_totals'] ?? true),
        ])->render();
    }
}

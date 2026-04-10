<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Builder\Block;

class DividerBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'divider';
    }

    public static function label(): string
    {
        return 'Scheiding';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())->label(self::label())->icon('heroicon-o-minus')->schema([]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.divider')->render();
    }
}

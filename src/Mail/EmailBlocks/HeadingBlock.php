<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class HeadingBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'heading';
    }

    public static function label(): string
    {
        return 'Kop';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-bars-3-bottom-left')
            ->schema([
                TextInput::make('text')->label('Tekst')->required(),
                Select::make('level')
                    ->label('Grootte')
                    ->options(['h1' => 'Groot', 'h2' => 'Middel', 'h3' => 'Klein'])
                    ->default('h2')
                    ->required(),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.heading', [
            'text' => self::substitute($blockData['text'] ?? '', $context),
            'level' => $blockData['level'] ?? 'h2',
        ])->render();
    }
}

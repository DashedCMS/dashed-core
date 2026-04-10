<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Builder\Block;

class ButtonBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'button';
    }

    public static function label(): string
    {
        return 'Knop';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-cursor-arrow-rays')
            ->schema([
                TextInput::make('label')->label('Label')->required(),
                TextInput::make('url')->label('URL')->required(),
                ColorPicker::make('background')->default('#111827'),
                ColorPicker::make('color')->default('#ffffff'),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.button', [
            'label' => self::substitute($blockData['label'] ?? '', $context),
            'url' => self::substitute($blockData['url'] ?? '#', $context),
            'background' => $blockData['background'] ?? '#111827',
            'color' => $blockData['color'] ?? '#ffffff',
        ])->render();
    }
}

<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class SpacerBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'spacer';
    }

    public static function label(): string
    {
        return __('Witruimte');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-arrows-up-down')
            ->schema([
                TextInput::make('height')
                    ->label(__('Hoogte in pixels'))
                    ->numeric()
                    ->default(24),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.spacer', [
            'height' => (int) ($blockData['height'] ?? 24),
        ])->render();
    }
}

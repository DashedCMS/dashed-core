<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class HeadingBlock extends EmailBlock
{
    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function key(): string
    {
        return 'heading';
    }

    public static function label(): string
    {
        return __('Kop');
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-bars-3-bottom-left')
            ->schema([
                TextInput::make('text')->label(__('Tekst'))->required(),
                Select::make('level')
                    ->label(__('Grootte'))
                    ->options(['h1' => __('Groot'), 'h2' => __('Middel'), 'h3' => __('Klein')])
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

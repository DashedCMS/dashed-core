<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class QuoteBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'quote';
    }

    public static function label(): string
    {
        return __('Citaat');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Textarea::make('text')->label(__('Citaat'))->rows(3)->required(),
                TextInput::make('source')->label(__('Van wie')),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.quote', [
            'text' => self::substitute((string) ($blockData['text'] ?? ''), $context),
            'source' => self::substitute((string) ($blockData['source'] ?? ''), $context) ?: null,
            'primaryColor' => $context['primaryColor'] ?? '#111827',
        ])->render();
    }
}

<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Builder\Block;

class TextBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'text';
    }

    public static function label(): string
    {
        return 'Tekst';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-document-text')
            ->schema([
                RichEditor::make('body')
                    ->label('Inhoud')
                    ->required(),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.text', [
            'body' => self::substitute($blockData['body'] ?? '', $context),
        ])->render();
    }
}

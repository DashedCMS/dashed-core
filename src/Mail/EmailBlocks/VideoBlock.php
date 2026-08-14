<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class VideoBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'video';
    }

    public static function label(): string
    {
        return __('Video');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-play')
            ->schema([
                TextInput::make('image')
                    ->label(__('Voorbeeldafbeelding URL'))
                    ->helperText(__('Mailprogramma\'s spelen geen video af, dus dit wordt een klikbare afbeelding.'))
                    ->required(),
                TextInput::make('url')->label(__('Link naar de video'))->required(),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.video', [
            'image' => self::substitute((string) ($blockData['image'] ?? ''), $context),
            'url' => self::substitute((string) ($blockData['url'] ?? '#'), $context),
        ])->render();
    }
}

<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class ImageBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'image';
    }

    public static function label(): string
    {
        return 'Afbeelding';
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-photo')
            ->schema([
                mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                TextInput::make('alt')->label('Alt-tekst'),
                TextInput::make('url')->label('Link (optioneel)'),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        $media = mediaHelper()->getSingleMedia($blockData['image'] ?? null);

        return view('dashed-core::emails.blocks.image', [
            'src' => $media?->getFullUrl(),
            'alt' => $blockData['alt'] ?? '',
            'url' => $blockData['url'] ?? null,
        ])->render();
    }
}

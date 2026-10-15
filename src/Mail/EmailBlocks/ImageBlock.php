<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class ImageBlock extends EmailBlock
{
    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function key(): string
    {
        return 'image';
    }

    public static function label(): string
    {
        return __('Afbeelding');
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-photo')
            ->schema([
                mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                TextInput::make('alt')->label(__('Alt-tekst')),
                TextInput::make('url')->label(__('Link (optioneel)')),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        // getSingleMedia() geeft '' bij niets, een object met een
        // url-eigenschap bij een media-item en een geplakte URL ongewijzigd
        // terug; getFullUrl() bestaat op geen van drie.
        $media = ($blockData['image'] ?? null) ? mediaHelper()->getSingleMedia($blockData['image']) : '';

        $src = match (true) {
            is_object($media) => (string) ($media->url ?? ''),
            is_string($media) => $media,
            default => '',
        };

        return view('dashed-core::emails.blocks.image', [
            'src' => $src,
            'alt' => $blockData['alt'] ?? '',
            'url' => $blockData['url'] ?? null,
        ])->render();
    }
}

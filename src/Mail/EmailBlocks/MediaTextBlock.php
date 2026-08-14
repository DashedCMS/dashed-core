<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;

class MediaTextBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'media-text';
    }

    public static function label(): string
    {
        return __('Afbeelding met tekst');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-photo')
            ->schema([
                TextInput::make('image')->label(__('Afbeelding URL'))->required(),
                Textarea::make('text')->label(__('Tekst'))->rows(4)->required(),
                Select::make('position')
                    ->label(__('Afbeelding staat'))
                    ->options([
                        'left' => __('Links'),
                        'right' => __('Rechts'),
                    ])
                    ->default('left'),
                TextInput::make('button_label')->label(__('Knoptekst')),
                TextInput::make('button_url')->label(__('Knop URL')),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.media-text', [
            'image' => self::substitute((string) ($blockData['image'] ?? ''), $context),
            'text' => self::substitute((string) ($blockData['text'] ?? ''), $context),
            'rechts' => ($blockData['position'] ?? 'left') === 'right',
            'buttonLabel' => self::substitute((string) ($blockData['button_label'] ?? ''), $context) ?: null,
            'buttonUrl' => self::substitute((string) ($blockData['button_url'] ?? ''), $context) ?: null,
            'primaryColor' => $context['primaryColor'] ?? '#111827',
            'textColor' => $context['textColor'] ?? '#ffffff',
        ])->render();
    }
}

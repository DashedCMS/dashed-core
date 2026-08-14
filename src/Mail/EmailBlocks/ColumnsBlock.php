<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Builder\Block;

class ColumnsBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'columns';
    }

    public static function label(): string
    {
        return __('Twee kolommen');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-view-columns')
            ->schema([
                Fieldset::make(__('Linkerkolom'))->schema([
                    TextInput::make('left_image')->label(__('Afbeelding URL')),
                    Textarea::make('left_text')->label(__('Tekst'))->rows(3),
                    TextInput::make('left_button_label')->label(__('Knoptekst')),
                    TextInput::make('left_button_url')->label(__('Knop URL')),
                ]),
                Fieldset::make(__('Rechterkolom'))->schema([
                    TextInput::make('right_image')->label(__('Afbeelding URL')),
                    Textarea::make('right_text')->label(__('Tekst'))->rows(3),
                    TextInput::make('right_button_label')->label(__('Knoptekst')),
                    TextInput::make('right_button_url')->label(__('Knop URL')),
                ]),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        return view('dashed-core::emails.blocks.columns', [
            'left' => self::kolom($blockData, 'left', $context),
            'right' => self::kolom($blockData, 'right', $context),
            'primaryColor' => $context['primaryColor'] ?? '#111827',
            'textColor' => $context['textColor'] ?? '#ffffff',
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $blockData
     * @param  array<string, mixed>  $context
     * @return array<string, string|null>
     */
    private static function kolom(array $blockData, string $kant, array $context): array
    {
        return [
            'image' => self::substitute((string) ($blockData[$kant.'_image'] ?? ''), $context) ?: null,
            'text' => self::substitute((string) ($blockData[$kant.'_text'] ?? ''), $context) ?: null,
            'buttonLabel' => self::substitute((string) ($blockData[$kant.'_button_label'] ?? ''), $context) ?: null,
            'buttonUrl' => self::substitute((string) ($blockData[$kant.'_button_url'] ?? ''), $context) ?: null,
        ];
    }
}

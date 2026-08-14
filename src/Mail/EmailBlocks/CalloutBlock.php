<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Builder\Block;

class CalloutBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'callout';
    }

    public static function label(): string
    {
        return __('Uitgelicht kader');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-megaphone')
            ->schema([
                Textarea::make('text')->label(__('Tekst'))->rows(3)->required(),
                ColorPicker::make('background')
                    ->label(__('Achtergrondkleur'))
                    ->helperText(__('Laat leeg om de primaire kleur van de mail te gebruiken.')),
                ColorPicker::make('color')->label(__('Tekstkleur')),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        // Dit is het enige blok met een eigen kleurkeuze. Leeg gelaten valt
        // het terug op de context en niet op een eigen standaardwaarde, want
        // anders wint het blok altijd van de huisstijl van de mail.
        return view('dashed-core::emails.blocks.callout', [
            'text' => self::substitute((string) ($blockData['text'] ?? ''), $context),
            'background' => ($blockData['background'] ?? null) ?: ($context['primaryColor'] ?? '#111827'),
            'color' => ($blockData['color'] ?? null) ?: ($context['textColor'] ?? '#ffffff'),
        ])->render();
    }
}

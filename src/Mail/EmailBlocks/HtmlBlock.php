<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Builder\Block;

class HtmlBlock extends EmailBlock
{
    public static function key(): string
    {
        return 'html';
    }

    public static function label(): string
    {
        return __('Eigen HTML');
    }

    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL, self::CONTEXT_NEWSLETTER];
    }

    public static function filamentBlock(): Block
    {
        return Block::make(self::key())
            ->label(self::label())
            ->icon('heroicon-o-code-bracket')
            ->schema([
                Textarea::make('html')
                    ->label(__('HTML'))
                    ->rows(8)
                    ->helperText(__('Wordt onbewerkt in de mail gezet. Gebruik tabellen en inline stijlen.'))
                    ->required(),
            ]);
    }

    public static function render(array $blockData, array $context): string
    {
        // Bewust niet ontsnappen: dit blok bestaat juist om onbewerkte HTML
        // door te laten. Wie dit blok kan invullen is een ingelogde
        // beheerder met toegang tot de campagnebouwer, geen anonieme
        // bezoeker, dus is er hier geen vreemde invoer om tegen te
        // beveiligen.
        return '<tr><td style="padding:16px 24px;">'
            .self::substitute((string) ($blockData['html'] ?? ''), $context)
            .'</td></tr>';
    }
}

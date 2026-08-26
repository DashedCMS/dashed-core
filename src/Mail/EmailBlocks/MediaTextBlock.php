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
                // Een mediakiezer, net als bij ImageBlock. Dit was een kaal
                // tekstveld waar een redacteur zelf een URL in moest plakken,
                // en daardoor was het blok er wel maar gebruikte niemand het.
                mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
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
            // getSingleMedia() vertaalt een media-id naar een URL en laat een
            // waarde die al een URL is ongewijzigd. Daardoor blijven blokken
            // uit bestaande campagnes, die nog een geplakte URL dragen, gewoon
            // werken.
            'image' => self::afbeelding($blockData['image'] ?? null, $context),
            'text' => self::substitute((string) ($blockData['text'] ?? ''), $context),
            'rechts' => ($blockData['position'] ?? 'left') === 'right',
            'buttonLabel' => self::substitute((string) ($blockData['button_label'] ?? ''), $context) ?: null,
            'buttonUrl' => self::substitute((string) ($blockData['button_url'] ?? ''), $context) ?: null,
            'primaryColor' => $context['primaryColor'] ?? '#111827',
            'textColor' => $context['textColor'] ?? '#ffffff',
        ])->render();
    }

    /**
     * De URL van de gekozen afbeelding.
     *
     * getSingleMedia() geeft '' terug als er niets is, een object met een
     * url-eigenschap bij een media-item, en de waarde ongewijzigd als het al
     * een URL is. Alle drie de gevallen komen voor: een vers gekozen
     * afbeelding, een blok uit een oude campagne, en een blok waar niets in
     * staat.
     */
    private static function afbeelding(mixed $waarde, array $context): string
    {
        if (! $waarde) {
            return '';
        }

        $media = mediaHelper()->getSingleMedia($waarde);

        $url = match (true) {
            is_object($media) => (string) ($media->url ?? ''),
            is_string($media) => $media,
            default => '',
        };

        return self::substitute($url, $context);
    }
}

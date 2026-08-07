<?php

namespace Dashed\DashedCore\Filament\Resources\SentEmailResource\Pages;

use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Dashed\DashedCore\Filament\Resources\SentEmailResource;

class ViewSentEmail extends ViewRecord
{
    protected static string $resource = SentEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Details'))
                ->schema([
                    TextEntry::make('to_email')
                        ->label(__('Ontvanger')),
                    TextEntry::make('subject')
                        ->label(__('Onderwerp')),
                    TextEntry::make('status')
                        ->label(__('Status'))
                        ->badge(),
                    TextEntry::make('created_at')
                        ->label(__('Verzonden'))
                        ->dateTime(),
                    TextEntry::make('delivered_at')
                        ->label(__('Afgeleverd'))
                        ->dateTime()
                        ->placeholder(__('-')),
                    TextEntry::make('opened_at')
                        ->label(__('Geopend'))
                        ->dateTime()
                        ->placeholder(__('-')),
                    TextEntry::make('open_count')
                        ->label(__('Aantal opens')),
                    TextEntry::make('clicked_at')
                        ->label(__('Geklikt'))
                        ->dateTime()
                        ->placeholder(__('-')),
                    TextEntry::make('click_count')
                        ->label(__('Aantal clicks')),
                    TextEntry::make('bounced_at')
                        ->label(__('Gebounced'))
                        ->dateTime()
                        ->placeholder(__('-')),
                    TextEntry::make('bounce_reason')
                        ->label(__('Bounce-reden'))
                        ->placeholder(__('-')),
                    TextEntry::make('attachments')
                        ->label(__('Bijlagen'))
                        ->placeholder(__('-'))
                        ->formatStateUsing(fn ($state): ?string => self::formatAttachmentState($state)),
                ])
                ->columns(2),
            Section::make(__('Preview'))
                ->schema([
                    ViewEntry::make('html_body')
                        ->view('dashed-core::filament.sent-email-preview'),
                ]),
        ]);
    }

    /**
     * Formatteert de bijlage-state naar een leesbare regel. Filament kan deze
     * formatter per bijlage aanroepen (dan is $state een enkele bijlage) of met
     * de volledige lijst. Beide vormen worden ondersteund, plus lege waarden.
     */
    public static function formatAttachmentState($state): ?string
    {
        if (empty($state)) {
            return null;
        }

        // Enkele bijlage: een associatieve array (geen numerieke lijst).
        if (is_array($state) && ! array_is_list($state)) {
            return self::formatAttachmentItem($state);
        }

        // Lijst van bijlagen.
        if (is_array($state)) {
            return implode(', ', array_map(
                fn ($item) => is_array($item) ? self::formatAttachmentItem($item) : (string) $item,
                $state
            ));
        }

        return (string) $state;
    }

    protected static function formatAttachmentItem(array $item): string
    {
        return sprintf(
            '%s (%s, %s KB)',
            $item['filename'] ?? 'onbekend',
            $item['mime'] ?? '',
            round(((int) ($item['size'] ?? 0)) / 1024)
        );
    }
}

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
            Section::make('Details')
                ->schema([
                    TextEntry::make('to_email')
                        ->label('Ontvanger'),
                    TextEntry::make('subject')
                        ->label('Onderwerp'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),
                    TextEntry::make('created_at')
                        ->label('Verzonden')
                        ->dateTime(),
                    TextEntry::make('delivered_at')
                        ->label('Afgeleverd')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('opened_at')
                        ->label('Geopend')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('open_count')
                        ->label('Aantal opens'),
                    TextEntry::make('clicked_at')
                        ->label('Geklikt')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('click_count')
                        ->label('Aantal clicks'),
                    TextEntry::make('bounced_at')
                        ->label('Gebounced')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('bounce_reason')
                        ->label('Bounce-reden')
                        ->placeholder('-'),
                    TextEntry::make('attachments')
                        ->label('Bijlagen')
                        ->placeholder('-')
                        ->formatStateUsing(function ($state): ?string {
                            if (empty($state)) {
                                return null;
                            }

                            return implode(', ', array_map(
                                fn (array $item) => sprintf(
                                    '%s (%s, %s KB)',
                                    $item['filename'] ?? 'onbekend',
                                    $item['mime'],
                                    round($item['size'] / 1024)
                                ),
                                $state
                            ));
                        }),
                ])
                ->columns(2),
            Section::make('Preview')
                ->schema([
                    ViewEntry::make('html_body')
                        ->view('dashed-core::filament.sent-email-preview'),
                ]),
        ]);
    }
}

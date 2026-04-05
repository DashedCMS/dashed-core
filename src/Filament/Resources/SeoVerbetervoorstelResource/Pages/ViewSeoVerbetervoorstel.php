<?php

namespace Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource;
use Dashed\DashedCore\Models\Customsetting;

class ViewSeoVerbetervoorstel extends ViewRecord
{
    protected static string $resource = SeoVerbetervoorstelResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Status')
                ->schema([
                    TextEntry::make('status_label')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_color),
                    TextEntry::make('subject_type')
                        ->label('Type')
                        ->formatStateUsing(fn ($state) => class_basename($state)),
                    TextEntry::make('subject_id')
                        ->label('Record ID'),
                    TextEntry::make('created_at')
                        ->label('Aangemaakt op')
                        ->dateTime('d-m-Y H:i'),
                    TextEntry::make('applied_at')
                        ->label('Toegepast op')
                        ->dateTime('d-m-Y H:i')
                        ->placeholder('Nog niet toegepast'),
                ])
                ->columns(3),

            Section::make('Samenvatting')
                ->schema([
                    TextEntry::make('analysis_summary')
                        ->label('')
                        ->markdown()
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->analysis_summary),

            Section::make('Keyword research')
                ->schema([
                    TextEntry::make('keyword_research.primary_keyword')
                        ->label('Primair keyword'),
                    TextEntry::make('keyword_research.secondary_keywords')
                        ->label('Secundaire keywords')
                        ->listWithLineBreaks()
                        ->bulleted(),
                    TextEntry::make('keyword_research.long_tail_keywords')
                        ->label('Long-tail keywords')
                        ->listWithLineBreaks()
                        ->bulleted(),
                    TextEntry::make('keyword_research.missing_opportunities')
                        ->label('Gemiste kansen')
                        ->listWithLineBreaks()
                        ->bulleted(),
                ])
                ->columns(2)
                ->visible(fn ($record) => ! empty($record->keyword_research)),

            Section::make('Veld verbetervoorstellen')
                ->schema(function ($record) {
                    if (empty($record->field_proposals)) {
                        return [TextEntry::make('no_proposals')->label('')->state('Geen voorstellen beschikbaar.')];
                    }

                    $entries = [];
                    foreach ($record->field_proposals as $i => $proposal) {
                        $acceptedLabel = match ($proposal['accepted'] ?? null) {
                            true => ' (geaccepteerd)',
                            false => ' (geweigerd)',
                            default => '',
                        };

                        $entries[] = Section::make(($proposal['label'] ?? $proposal['field']) . $acceptedLabel)
                            ->schema([
                                TextEntry::make("field_proposals.{$i}.current")
                                    ->label('Huidige waarde')
                                    ->placeholder('(leeg)'),
                                TextEntry::make("field_proposals.{$i}.proposed")
                                    ->label('Voorgestelde waarde')
                                    ->color('success'),
                                TextEntry::make("field_proposals.{$i}.reason")
                                    ->label('Reden')
                                    ->columnSpanFull(),
                                TextEntry::make("field_proposals.{$i}.priority")
                                    ->label('Prioriteit')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'hoog' => 'danger',
                                        'gemiddeld' => 'warning',
                                        'laag' => 'gray',
                                        default => 'gray',
                                    }),
                                TextEntry::make("field_proposals.{$i}.improvement_type")
                                    ->label('Type verbetering')
                                    ->badge()
                                    ->color('info'),
                            ])
                            ->columns(2)
                            ->compact();
                    }

                    return $entries;
                })
                ->visible(fn ($record) => $record->status === 'ready' || $record->status === 'applied'),

            Section::make('Foutmelding')
                ->schema([
                    TextEntry::make('error_message')
                        ->label('')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->status === 'failed'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apply')
                ->label('Verbeteringen toepassen')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'ready')
                ->schema(function () {
                    $fields = [];
                    foreach ($this->record->field_proposals ?? [] as $i => $proposal) {
                        $fields[] = Toggle::make("accept_{$i}")
                            ->label("{$proposal['label']}: {$proposal['proposed']}")
                            ->helperText($proposal['reason'] ?? '')
                            ->default(true);
                    }

                    return $fields;
                })
                ->action(function (array $data): void {
                    $record = $this->record;
                    $subject = $record->subject;

                    if (! $subject) {
                        Notification::make()->title('Record niet gevonden')->danger()->send();

                        return;
                    }

                    $proposals = $record->field_proposals ?? [];
                    $applied = 0;

                    foreach ($proposals as $i => $proposal) {
                        $proposals[$i]['accepted'] = $data["accept_{$i}"] ?? false;

                        if (! ($data["accept_{$i}"] ?? false)) {
                            continue;
                        }

                        $field = $proposal['field'];
                        $value = $proposal['proposed'];

                        // Apply to metadata fields
                        if (in_array($field, ['meta_title', 'meta_description']) && $subject->metadata) {
                            $metaField = $field === 'meta_title' ? 'title' : 'description';
                            $locale = app()->getLocale();

                            $subject->metadata->setTranslation($metaField, $locale, $value);
                            $subject->metadata->saveQuietly();
                            $applied++;
                        }

                        // Apply directly to model fields
                        if (! in_array($field, ['meta_title', 'meta_description']) && isset($subject->$field)) {
                            if (in_array($field, $subject->translatable ?? [])) {
                                $subject->setTranslation($field, app()->getLocale(), $value);
                            } else {
                                $subject->$field = $value;
                            }
                            $subject->saveQuietly();
                            $applied++;
                        }
                    }

                    $record->update([
                        'field_proposals' => $proposals,
                        'status' => 'applied',
                        'applied_by' => auth()->id(),
                        'applied_at' => now(),
                    ]);

                    Notification::make()
                        ->title("{$applied} verbetering(en) toegepast")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'field_proposals', 'applied_at']);
                }),

            Action::make('refresh')
                ->label('Ververs status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'analyzing')
                ->action(function (): void {
                    $this->record->refresh();
                    $this->refreshFormData(['status', 'analysis_summary', 'keyword_research', 'field_proposals', 'error_message']);
                }),
        ];
    }
}

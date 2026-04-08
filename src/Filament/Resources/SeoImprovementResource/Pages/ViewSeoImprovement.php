<?php

namespace Dashed\DashedCore\Filament\Resources\SeoImprovementResource\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Jobs\AnalyzeSeoJob;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Filament\Resources\SeoImprovementResource;

class ViewSeoImprovement extends ViewRecord
{
    protected static string $resource = SeoImprovementResource::class;

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
                ->columns(3)
                ->columnSpanFull(),

            Section::make('Bezig met analyseren...')
                ->schema([
                    TextEntry::make('progress_message')
                        ->label('')
                        ->placeholder('Analyse wordt gestart...')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->status === 'analyzing')
                ->extraAttributes(['wire:poll.3s' => 'refreshPolledData'])
                ->columnSpanFull(),

            Section::make('Samenvatting')
                ->schema([
                    TextEntry::make('analysis_summary')
                        ->label('')
                        ->markdown()
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->analysis_summary)
                ->columnSpanFull(),

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
                ->visible(fn ($record) => ! empty($record->keyword_research))
                ->columnSpanFull(),

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
                ->visible(fn ($record) => $record->status === 'ready' || $record->status === 'applied')
                ->columnSpanFull(),

            Section::make('Blok voorstellen')
                ->schema(function ($record) {
                    if (empty($record->block_proposals)) {
                        return [TextEntry::make('no_block_proposals')->label('')->state('Geen blok voorstellen.')];
                    }

                    $entries = [];
                    foreach ($record->block_proposals as $i => $proposal) {
                        $action = $proposal['action'] ?? 'add';
                        $acceptedLabel = match ($proposal['accepted'] ?? null) {
                            true => ' (geaccepteerd)',
                            false => ' (geweigerd)',
                            default => '',
                        };

                        $title = ($proposal['label'] ?? $proposal['block_type']) . $acceptedLabel;

                        if ($action === 'update') {
                            $blockIdx = $proposal['block_index'] ?? null;
                            $currentBlockData = [];

                            try {
                                if ($blockIdx !== null && $record->subject) {
                                    $blocks = method_exists($record->subject, 'getTranslation')
                                        ? ($record->subject->getTranslation('content', app()->getLocale()) ?: [])
                                        : ($record->subject->content ?? []);
                                    $currentBlockData = $blocks[$blockIdx]['data'] ?? [];
                                }
                            } catch (\Throwable) {
                            }
                            $flatFields = $this->flattenData($currentBlockData);
                            $fieldUpdates = $proposal['field_updates'] ?? [];

                            $fieldEntries = [];
                            foreach ($fieldUpdates as $path => $proposedValue) {
                                $safeKey = str_replace('.', '_', $path);
                                $isNew = ! isset($flatFields[$path]);
                                $isHtml = is_string($proposedValue) && (bool) preg_match('/<[a-z][^>]*>/i', $proposedValue);

                                if ($isHtml) {
                                    // Resolve current raw HTML from block data
                                    $currentRaw = $currentBlockData;
                                    foreach (explode('.', $path) as $seg) {
                                        $currentRaw = is_array($currentRaw) ? ($currentRaw[$seg] ?? '') : '';
                                    }
                                    $fieldEntries[] = Section::make($path)
                                        ->schema([
                                            TextEntry::make("block_proposals.{$i}.current_{$safeKey}_html")
                                                ->label('Huidig')
                                                ->state(is_string($currentRaw) ? $currentRaw : '')
                                                ->html()
                                                ->color('gray')
                                                ->columnSpanFull(),
                                            TextEntry::make("block_proposals.{$i}.proposed_{$safeKey}_html")
                                                ->label('Voorgesteld')
                                                ->state((string) $proposedValue)
                                                ->html()
                                                ->color('success')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->compact();
                                } else {
                                    $currentValue = $flatFields[$path] ?? '(nieuw)';
                                    $fieldEntries[] = Section::make($path)
                                        ->schema([
                                            TextEntry::make("block_proposals.{$i}.current_{$safeKey}")
                                                ->label('Huidig')
                                                ->state($currentValue)
                                                ->color('gray'),
                                            TextEntry::make("block_proposals.{$i}.proposed_{$safeKey}")
                                                ->label($isNew ? 'Nieuw' : 'Voorgesteld')
                                                ->state((string) $proposedValue)
                                                ->color('success'),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull()
                                        ->compact();
                                }
                            }

                            $fieldSections = $fieldEntries;

                            $entries[] = Section::make($title)
                                ->schema([
                                    TextEntry::make("block_proposals.{$i}.action")
                                        ->label('Actie')
                                        ->badge()
                                        ->color('warning'),
                                    TextEntry::make("block_proposals.{$i}.block_type")
                                        ->label('Bloktype'),
                                    TextEntry::make("block_proposals.{$i}.priority")
                                        ->label('Prioriteit')
                                        ->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'hoog' => 'danger',
                                            'gemiddeld' => 'warning',
                                            default => 'gray',
                                        }),
                                    TextEntry::make("block_proposals.{$i}.reason")
                                        ->label('Reden')
                                        ->columnSpanFull(),
                                    ...$fieldSections,
                                ])
                                ->columns(3)
                                ->compact();
                        }
                    }

                    return $entries;
                })
                ->visible(fn ($record) => ($record->status === 'ready' || $record->status === 'applied') && ! empty($record->block_proposals))
                ->columnSpanFull(),

            Section::make('Foutmelding')
                ->schema([
                    TextEntry::make('error_message')
                        ->label('')
                        ->fontFamily('mono')
                        ->words(0)
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record->status === 'failed')
                ->columnSpanFull(),
        ]);
    }

    public function refreshPolledData(): void
    {
        $this->record = $this->record->fresh();
        $this->refreshFormData(['status', 'progress_message', 'analysis_summary', 'keyword_research', 'field_proposals', 'block_proposals', 'error_message']);
    }

    private function flattenData(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $path = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;
            if (is_string($value) && trim(strip_tags($value)) !== '') {
                $result[$path] = strip_tags($value);
            } elseif (is_array($value)) {
                $result = array_merge($result, $this->flattenData($value, $path));
            }
        }

        return $result;
    }

    protected function getFilamentEditUrl(): ?string
    {
        $subject = $this->record->subject;
        if (! $subject) {
            return null;
        }

        foreach (Filament::getResources() as $resource) {
            if ($resource::getModel() === get_class($subject)) {
                if (array_key_exists('edit', $resource::getPages())) {
                    return $resource::getUrl('edit', ['record' => $subject->getKey()]);
                }
            }
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_subject')
                ->label('Bekijk in CMS')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->getFilamentEditUrl())
                ->openUrlInNewTab()
                ->visible(fn () => $this->getFilamentEditUrl() !== null),

            Action::make('apply')
                ->label('Verbeteringen toepassen')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'ready')
                ->schema(function () {
                    $fields = [];

                    foreach ($this->record->field_proposals ?? [] as $i => $proposal) {
                        $fields[] = Toggle::make("field_{$i}")
                            ->label("{$proposal['label']}: {$proposal['proposed']}")
                            ->helperText($proposal['reason'] ?? '')
                            ->default(true);
                    }

                    foreach ($this->record->block_proposals ?? [] as $i => $proposal) {
                        if (($proposal['action'] ?? '') !== 'update') {
                            continue;
                        }
                        $detail = implode(', ', array_keys($proposal['field_updates'] ?? []));
                        $fields[] = Toggle::make("block_{$i}")
                            ->label('Blok aanpassen: ' . ($proposal['label'] ?? $proposal['block_type']) . " ({$detail})")
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

                    $applied = 0;
                    $locale = app()->getLocale();

                    // Apply field proposals
                    $fieldProposals = $record->field_proposals ?? [];
                    foreach ($fieldProposals as $i => $proposal) {
                        $accepted = $data["field_{$i}"] ?? false;
                        $fieldProposals[$i]['accepted'] = $accepted;

                        if (! $accepted) {
                            continue;
                        }

                        $field = $proposal['field'];
                        $value = $proposal['proposed'];

                        if (str_contains(strtolower($field), 'slug')) {
                            continue;
                        }

                        if (in_array($field, ['meta_title', 'meta_description']) && $subject->metadata) {
                            $metaField = $field === 'meta_title' ? 'title' : 'description';
                            $subject->metadata->setTranslation($metaField, $locale, $value);
                            $subject->metadata->saveQuietly();
                            $applied++;
                        } elseif (! in_array($field, ['meta_title', 'meta_description']) && isset($subject->$field)) {
                            if (in_array($field, $subject->translatable ?? [])) {
                                $subject->setTranslation($field, $locale, $value);
                            } else {
                                $subject->$field = $value;
                            }
                            $subject->saveQuietly();
                            $applied++;
                        }
                    }

                    // Apply block proposals
                    $blockProposals = $record->block_proposals ?? [];
                    $needsBlockSave = false;
                    $currentBlocks = in_array('content', $subject->translatable ?? [])
                        ? ($subject->getTranslation('content', $locale) ?: [])
                        : [];

                    foreach ($blockProposals as $i => $proposal) {
                        $accepted = $data["block_{$i}"] ?? false;
                        $blockProposals[$i]['accepted'] = $accepted;

                        if (! $accepted) {
                            continue;
                        }

                        if (($proposal['action'] ?? '') === 'update') {
                            $blockIndex = $proposal['block_index'] ?? null;
                            if ($blockIndex !== null && isset($currentBlocks[$blockIndex])) {
                                foreach ($proposal['field_updates'] ?? [] as $path => $value) {
                                    $keys = explode('.', $path);
                                    $ref = &$currentBlocks[$blockIndex]['data'];
                                    foreach ($keys as $k) {
                                        if (! isset($ref[$k])) {
                                            $ref[$k] = [];
                                        }
                                        $ref = &$ref[$k];
                                    }
                                    $ref = $value;
                                    unset($ref);
                                }
                                $needsBlockSave = true;
                                $applied++;
                            }
                        }
                    }

                    if ($needsBlockSave && in_array('content', $subject->translatable ?? [])) {
                        $subject->setTranslation('content', $locale, $currentBlocks);
                        $subject->saveQuietly();
                    }

                    $record->update([
                        'field_proposals' => $fieldProposals,
                        'block_proposals' => $blockProposals,
                        'status' => 'applied',
                        'applied_by' => auth()->id(),
                        'applied_at' => now(),
                    ]);

                    Notification::make()
                        ->title("{$applied} verbetering(en) toegepast")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'field_proposals', 'block_proposals', 'applied_at']);
                }),

            Action::make('retry')
                ->label('Opnieuw analyseren')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation(fn () => in_array($this->record->status, ['ready', 'applied']))
                ->modalHeading('Analyse opnieuw uitvoeren')
                ->modalDescription('De huidige resultaten worden overschreven. Weet je zeker dat je een nieuwe analyse wilt starten?')
                ->modalSubmitActionLabel('Ja, opnieuw analyseren')
                ->schema(function () {
                    return [
                        \Filament\Forms\Components\Textarea::make('instruction')
                            ->label('Extra instructie (optioneel)')
                            ->placeholder('Bijv: Focus op de FAQ sectie, of: geef een andere invalshoek')
                            ->rows(2),
                    ];
                })
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'analyzing',
                        'error_message' => null,
                        'keyword_research' => null,
                        'analysis_summary' => null,
                        'field_proposals' => null,
                        'block_proposals' => null,
                    ]);

                    AnalyzeSeoJob::dispatch(
                        $this->record,
                        Locales::getFirstLocale()['id'] ?? app()->getLocale(),
                        $data['instruction'] ?? '',
                    );

                    Notification::make()
                        ->title('Analyse opnieuw gestart')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'error_message', 'keyword_research', 'analysis_summary', 'field_proposals', 'block_proposals']);
                }),

            Action::make('refresh')
                ->label('Ververs status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->fresh()->status === 'analyzing')
                ->action(function (): void {
                    $this->record->refresh();
                    $this->refreshFormData(['status', 'analysis_summary', 'keyword_research', 'field_proposals', 'block_proposals', 'error_message']);
                }),
        ];
    }
}

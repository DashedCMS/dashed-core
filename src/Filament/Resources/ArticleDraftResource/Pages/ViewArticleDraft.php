<?php

namespace Dashed\DashedCore\Filament\Resources\ArticleDraftResource\Pages;

use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Jobs\GenerateArticleJob;
use Dashed\DashedCore\Models\ArticleDraft;
use Dashed\DashedCore\Filament\Resources\ArticleDraftResource;

class ViewArticleDraft extends ViewRecord
{
    protected static string $resource = ArticleDraftResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // Status + progress while generating
            Section::make(fn ($record) => in_array($record->status, ['pending', 'planning', 'writing'])
                ? ($record->progress_message ?: 'Artikel wordt gegenereerd...')
                : 'Status'
            )
                ->schema([
                    TextEntry::make('status_label')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_color),
                    TextEntry::make('keyword')
                        ->label('Zoekwoord'),
                    TextEntry::make('locale')
                        ->label('Taal')
                        ->badge(),
                    TextEntry::make('created_at')
                        ->label('Aangemaakt')
                        ->dateTime('d-m-Y H:i'),
                ])
                ->columns(4)
                ->extraAttributes(fn ($record) => in_array($record->status, ['pending', 'planning', 'writing'])
                    ? ['wire:poll.3s' => 'refreshPolledData']
                    : []
                )
                ->columnSpanFull(),

            // Content plan: keyword research
            Section::make('Keyword onderzoek')
                ->schema([
                    TextEntry::make('content_plan.keyword_research.primary_keyword')
                        ->label('Primair keyword'),
                    TextEntry::make('content_plan.keyword_research.search_intent')
                        ->label('Zoekintentie')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('content_plan.keyword_research.target_audience')
                        ->label('Doelgroep')
                        ->columnSpanFull(),
                    TextEntry::make('content_plan.keyword_research.secondary_keywords')
                        ->label('Secundaire keywords')
                        ->listWithLineBreaks()
                        ->bulleted(),
                    TextEntry::make('content_plan.keyword_research.long_tail_keywords')
                        ->label('Long-tail keywords')
                        ->listWithLineBreaks()
                        ->bulleted(),
                    TextEntry::make('content_plan.keyword_research.content_clusters')
                        ->label('Content clusters')
                        ->listWithLineBreaks()
                        ->bulleted(),
                ])
                ->columns(2)
                ->visible(fn ($record) => ! empty($record->content_plan['keyword_research']))
                ->columnSpanFull(),

            // Content plan: outline
            Section::make('Artikel opzet')
                ->schema(function ($record) {
                    $outline = $record->content_plan['outline'] ?? null;
                    if (! $outline) {
                        return [TextEntry::make('no_outline')->label('')->state('Opzet nog niet beschikbaar.')];
                    }

                    $entries = [
                        TextEntry::make('article_content.h1')
                            ->label('H1 — Artikel titel')
                            ->weight('bold')
                            ->columnSpanFull(),
                        TextEntry::make('article_content.meta_title')
                            ->label('Meta title'),
                        TextEntry::make('article_content.meta_description')
                            ->label('Meta description'),
                        TextEntry::make('article_content.excerpt')
                            ->label('Excerpt / samenvatting')
                            ->columnSpanFull(),
                    ];

                    foreach ($outline['sections'] ?? [] as $i => $section) {
                        $h3s = ! empty($section['h3s']) ? ' → ' . implode(' | ', $section['h3s']) : '';
                        $entries[] = TextEntry::make("content_plan.outline.sections.{$i}.h2")
                            ->label("H2 " . ($i + 1))
                            ->state(($section['h2'] ?? '') . $h3s)
                            ->columnSpanFull();
                    }

                    return $entries;
                })
                ->columns(2)
                ->visible(fn ($record) => ! empty($record->content_plan))
                ->columnSpanFull(),

            // Full article preview
            Section::make('Artikel inhoud')
                ->schema(function ($record) {
                    $content = $record->article_content;
                    if (empty($content)) {
                        return [TextEntry::make('no_content')->label('')->state('Artikel nog niet gegenereerd.')];
                    }

                    $entries = [];

                    if (! empty($content['introduction'])) {
                        $entries[] = Section::make('Introductie')
                            ->schema([
                                TextEntry::make('article_content.introduction')
                                    ->label('')
                                    ->html()
                                    ->words(0)
                                    ->columnSpanFull(),
                            ])
                            ->compact()
                            ->columnSpanFull();
                    }

                    foreach ($content['sections'] ?? [] as $i => $section) {
                        $entries[] = Section::make($section['h2'] ?? "Sectie " . ($i + 1))
                            ->schema([
                                TextEntry::make("article_content.sections.{$i}.content")
                                    ->label('')
                                    ->html()
                                    ->words(0)
                                    ->columnSpanFull(),
                            ])
                            ->compact()
                            ->columnSpanFull();
                    }

                    if (! empty($content['faq']['questions'])) {
                        $faqEntries = [];
                        foreach ($content['faq']['questions'] as $qi => $qa) {
                            $faqEntries[] = TextEntry::make("article_content.faq.questions.{$qi}.question")
                                ->label('')
                                ->state(($qi + 1) . '. ' . ($qa['question'] ?? ''))
                                ->weight('bold')
                                ->columnSpanFull();
                            $faqEntries[] = TextEntry::make("article_content.faq.questions.{$qi}.answer")
                                ->label('')
                                ->html()
                                ->words(0)
                                ->columnSpanFull();
                        }
                        $entries[] = Section::make($content['faq']['title'] ?? 'Veelgestelde vragen')
                            ->schema($faqEntries)
                            ->compact()
                            ->columnSpanFull();
                    }

                    if (! empty($content['conclusion'])) {
                        $entries[] = Section::make('Conclusie')
                            ->schema([
                                TextEntry::make('article_content.conclusion')
                                    ->label('')
                                    ->html()
                                    ->words(0)
                                    ->columnSpanFull(),
                            ])
                            ->compact()
                            ->columnSpanFull();
                    }

                    return $entries;
                })
                ->visible(fn ($record) => $record->status === 'ready' || $record->status === 'applied')
                ->columnSpanFull(),

            // Error
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
        $this->refreshFormData(['status', 'progress_message', 'content_plan', 'article_content', 'error_message']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apply')
                ->label('Toepassen als nieuw artikel')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(fn () => $this->record->status === 'ready')
                ->action(function (): void {
                    $this->applyToArticle();
                }),

            Action::make('retry')
                ->label('Opnieuw genereren')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation(fn () => in_array($this->record->status, ['ready', 'applied']))
                ->modalHeading('Artikel opnieuw genereren')
                ->modalDescription('De huidige inhoud wordt overschreven. Weet je zeker dat je een nieuw artikel wilt genereren?')
                ->modalSubmitActionLabel('Ja, opnieuw genereren')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('keyword')
                        ->label('Zoekwoord')
                        ->default(fn () => $this->record->keyword)
                        ->required(),
                    Textarea::make('instruction')
                        ->label('Extra instructie (optioneel)')
                        ->default(fn () => $this->record->instruction)
                        ->placeholder('Bijv: gebruik een andere invalshoek, focus meer op beginners')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'keyword' => $data['keyword'],
                        'instruction' => $data['instruction'] ?? null,
                        'status' => 'pending',
                        'progress_message' => null,
                        'error_message' => null,
                        'content_plan' => null,
                        'article_content' => null,
                        'applied_at' => null,
                    ]);

                    GenerateArticleJob::dispatch($this->record->fresh());

                    Notification::make()->title('Artikel wordt opnieuw gegenereerd')->success()->send();

                    $this->refreshFormData(['status', 'progress_message', 'content_plan', 'article_content', 'error_message']);
                }),
        ];
    }

    private function applyToArticle(): void
    {
        $content = $this->record->article_content;
        $locale = $this->record->locale;

        if (empty($content)) {
            Notification::make()->title('Geen inhoud beschikbaar')->danger()->send();
            return;
        }

        try {
            // Find the Article class from dashed-articles if available
            $articleClass = null;
            foreach (cms()->builder('routeModels') as $routeModel) {
                if (str_contains($routeModel['class'], 'Article') && ! str_contains($routeModel['class'], 'Category') && ! str_contains($routeModel['class'], 'Author')) {
                    $articleClass = $routeModel['class'];
                    break;
                }
            }

            if (! $articleClass || ! class_exists($articleClass)) {
                Notification::make()->title('Artikel module niet gevonden')->danger()->send();
                return;
            }

            $h1 = $content['h1'] ?? $this->record->keyword;
            $slug = Str::slug($h1);

            // Ensure unique slug
            $baseSlug = $slug;
            $counter = 1;
            while ($articleClass::whereJsonContains("slug->{$locale}", $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            // Build content blocks
            $blocks = [];

            // Introduction block
            if (! empty($content['introduction'])) {
                $blocks[] = ['type' => 'content', 'data' => ['content' => $content['introduction']]];
            }

            // Section blocks
            foreach ($content['sections'] ?? [] as $section) {
                if (! empty($section['content'])) {
                    $blocks[] = ['type' => 'content', 'data' => ['content' => $section['content']]];
                }
            }

            // FAQ block
            if (! empty($content['faq']['questions'])) {
                $questions = array_map(fn ($q) => [
                    'question' => $q['question'] ?? '',
                    'description' => $q['answer'] ?? '',
                ], $content['faq']['questions']);

                $blocks[] = [
                    'type' => 'faq',
                    'data' => [
                        'title' => $content['faq']['title'] ?? 'Veelgestelde vragen',
                        'subtitle' => $content['faq']['subtitle'] ?? '',
                        'buttons' => [],
                        'questions' => $questions,
                    ],
                ];
            }

            // Conclusion block
            if (! empty($content['conclusion'])) {
                $blocks[] = ['type' => 'content', 'data' => ['content' => $content['conclusion']]];
            }

            // Create the article
            $article = new $articleClass();
            $article->setTranslation('name', $locale, $h1);
            $article->setTranslation('slug', $locale, $slug);
            $article->setTranslation('excerpt', $locale, strip_tags($content['excerpt'] ?? ''));
            $article->setTranslation('content', $locale, $blocks);
            $article->public = false;
            $article->save();

            // Set metadata
            if ($article->metadata) {
                $article->metadata->setTranslation('title', $locale, $content['meta_title'] ?? $h1);
                $article->metadata->setTranslation('description', $locale, $content['meta_description'] ?? '');
                $article->metadata->save();
            } else {
                $article->metadata()->create([
                    'metadatable_type' => $articleClass,
                    'metadatable_id' => $article->id,
                ]);
                $article->refresh();
                if ($article->metadata) {
                    $article->metadata->setTranslation('title', $locale, $content['meta_title'] ?? $h1);
                    $article->metadata->setTranslation('description', $locale, $content['meta_description'] ?? '');
                    $article->metadata->save();
                }
            }

            // Update draft
            $this->record->update([
                'status' => 'applied',
                'applied_at' => now(),
                'applied_by' => auth()->id(),
                'subject_type' => $articleClass,
                'subject_id' => $article->id,
            ]);

            Notification::make()
                ->title('Artikel aangemaakt als concept')
                ->body("'{$h1}' is aangemaakt als niet-gepubliceerd artikel.")
                ->success()
                ->send();

            $this->refreshFormData(['status', 'applied_at']);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Fout bij aanmaken artikel')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

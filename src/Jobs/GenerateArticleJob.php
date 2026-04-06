<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\ArticleDraft;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Exceptions\ClaudeRateLimitException;

class GenerateArticleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 10;
    public int $timeout = 600;

    public function backoff(): array
    {
        return [15, 30, 60, 60, 60, 60, 60, 60, 60];
    }

    public function __construct(
        public ArticleDraft $draft,
    ) {}

    public function failed(\Throwable $exception): void
    {
        $this->draft->update([
            'status' => 'failed',
            'progress_message' => null,
            'error_message' => $exception->getMessage(),
        ]);
    }

    public function handle(): void
    {
        $attempt = $this->attempts();
        $siteName = Customsetting::get('site_name') ?: config('app.name');
        $brandContext = ClaudeHelper::getBrandContext();
        $instructionLine = $this->draft->instruction ? "\nEXTRA INSTRUCTIE: {$this->draft->instruction}\n" : '';
        $internalLinks = $this->collectInternalLinks();
        $keyword = $this->draft->keyword;
        $locale = $this->draft->locale;

        // Step 1: Keyword research + content cluster analysis
        $progressPrefix = $attempt > 1 ? " (poging {$attempt}/{$this->tries})" : '';
        $this->draft->update(['status' => 'planning']);
        $this->draft->setProgress("Stap 1/3 — Zoekwoorden en content clusters analyseren...{$progressPrefix}");

        try {
            $research = ClaudeHelper::runJsonPrompt($this->buildResearchPrompt($keyword, $siteName, $brandContext, $instructionLine, $internalLinks, $locale));
        } catch (ClaudeRateLimitException $e) {
            $this->releaseWithRateLimitMessage($e, 'Stap 1/3');
            return;
        } catch (\Throwable $e) {
            $this->draft->setProgress("Stap 1/3 — mislukt, opnieuw proberen... ({$e->getMessage()})");
            throw $e;
        }

        // Step 2: Build outline (H1 + all headings + section notes)
        $this->draft->setProgress("Stap 2/3 — Artikel opzet & structuur bouwen...");

        try {
            $outline = ClaudeHelper::runJsonPrompt($this->buildOutlinePrompt($keyword, $research, $siteName, $brandContext, $instructionLine, $internalLinks, $locale));
        } catch (ClaudeRateLimitException $e) {
            $this->releaseWithRateLimitMessage($e, 'Stap 2/3');
            return;
        } catch (\Throwable $e) {
            $this->draft->setProgress("Stap 2/3 — mislukt, opnieuw proberen... ({$e->getMessage()})");
            throw $e;
        }

        $this->draft->update([
            'content_plan' => [
                'keyword_research' => $research,
                'outline' => $outline,
            ],
        ]);

        // Step 3: Write all sections + intro + FAQ + conclusion
        $sections = $outline['sections'] ?? [];
        $totalSections = count($sections);
        $this->draft->update(['status' => 'writing']);

        // Intro
        $this->draft->setProgress("Stap 3/3 — Introductie schrijven...");
        try {
            $introResult = ClaudeHelper::runJsonPrompt($this->buildIntroPrompt($keyword, $outline, $research, $siteName, $brandContext, $instructionLine, $locale), maxTokens: 1000);
            $introduction = $introResult['introduction'] ?? '';
        } catch (ClaudeRateLimitException $e) {
            $this->releaseWithRateLimitMessage($e, 'Introductie');
            return;
        } catch (\Throwable $e) {
            $this->draft->setProgress("Introductie — mislukt, opnieuw proberen... ({$e->getMessage()})");
            throw $e;
        }

        // Sections
        $writtenSections = [];
        foreach ($sections as $i => $section) {
            $sectionNum = $i + 1;
            $this->draft->setProgress("Stap 3/3 — Sectie {$sectionNum}/{$totalSections} schrijven: \"{$section['h2']}\"...");
            try {
                $sectionResult = ClaudeHelper::runJsonPrompt(
                    $this->buildSectionPrompt($keyword, $outline, $section, $research, $siteName, $brandContext, $instructionLine, $internalLinks, $locale),
                    maxTokens: 1500,
                );
                $writtenSections[] = [
                    'h2' => $section['h2'],
                    'content' => $sectionResult['content'] ?? '',
                ];
            } catch (ClaudeRateLimitException $e) {
                $this->releaseWithRateLimitMessage($e, "Sectie {$sectionNum}");
                return;
            } catch (\Throwable) {
                // Skip failed section, continue
                $writtenSections[] = [
                    'h2' => $section['h2'],
                    'content' => "<h2>{$section['h2']}</h2>",
                ];
            }
        }

        // FAQ
        $faq = null;
        $this->draft->setProgress('Stap 3/3 — FAQ schrijven...');
        try {
            $faqResult = ClaudeHelper::runJsonPrompt($this->buildFaqPrompt($keyword, $outline, $research, $siteName, $brandContext, $instructionLine, $locale), maxTokens: 1500);
            if (! empty($faqResult['questions'])) {
                $faq = [
                    'title' => $faqResult['title'] ?? 'Veelgestelde vragen',
                    'subtitle' => $faqResult['subtitle'] ?? '',
                    'questions' => $faqResult['questions'],
                ];
            }
        } catch (ClaudeRateLimitException $e) {
            $this->releaseWithRateLimitMessage($e, 'FAQ');
            return;
        } catch (\Throwable) {
            // FAQ is optional, continue
        }

        // Conclusion
        $conclusion = '';
        $this->draft->setProgress('Stap 3/3 — Conclusie schrijven...');
        try {
            $conclusionResult = ClaudeHelper::runJsonPrompt($this->buildConclusionPrompt($keyword, $outline, $research, $siteName, $brandContext, $instructionLine, $locale), maxTokens: 800);
            $conclusion = $conclusionResult['conclusion'] ?? '';
        } catch (ClaudeRateLimitException $e) {
            $this->releaseWithRateLimitMessage($e, 'Conclusie');
            return;
        } catch (\Throwable) {
            // Conclusion is optional, continue
        }

        $this->draft->update([
            'status' => 'ready',
            'progress_message' => null,
            'article_content' => [
                'h1' => $outline['h1'] ?? $keyword,
                'meta_title' => $outline['meta_title'] ?? '',
                'meta_description' => $outline['meta_description'] ?? '',
                'excerpt' => $outline['excerpt'] ?? '',
                'introduction' => $introduction,
                'sections' => $writtenSections,
                'faq' => $faq,
                'conclusion' => $conclusion,
            ],
        ]);
    }

    private function collectInternalLinks(): string
    {
        $links = [];
        try {
            foreach (cms()->builder('routeModels') as $routeModel) {
                $class = $routeModel['class'];
                $nameField = $routeModel['nameField'] ?? 'name';
                foreach ($class::limit(30)->get() as $r) {
                    $url = method_exists($r, 'getUrl') ? ($r->getUrl($this->draft->locale) ?? '') : '';
                    if (! $url) continue;
                    $name = '';
                    try {
                        $name = method_exists($r, 'getTranslation') ? $r->getTranslation($nameField, $this->draft->locale) : $r->$nameField;
                    } catch (\Throwable) {}
                    if ($name) {
                        $links[] = "- {$name}: {$url}";
                    }
                }
            }
        } catch (\Throwable) {}

        return implode("\n", array_slice($links, 0, 50));
    }

    private function buildResearchPrompt(string $keyword, string $siteName, string $brandContext, string $instructionLine, string $internalLinks, string $locale): string
    {
        $internalLinksStr = $internalLinks ? "BESCHIKBARE INTERNE PAGINA'S:\n{$internalLinks}" : '';

        return <<<PROMPT
        Je bent een SEO-specialist en content strateeg voor een Nederlandse website. Analyseer het opgegeven zoekwoord en stel een complete content strategie op.

        WEBSITE: {$siteName}
        TAAL: {$locale}
        {$brandContext}
        ZOEKWOORD: {$keyword}
        {$instructionLine}

        {$internalLinksStr}

        Analyseer:
        1. Wat is de zoekintentie van dit zoekwoord?
        2. Welke secundaire zoekwoorden en long-tail varianten zijn relevant?
        3. Welke gerelateerde onderwerpen vormen een content cluster?
        4. Wie is de doelgroep?
        5. Welke vragen stellen mensen over dit onderwerp? (voor FAQ)

        Retourneer UITSLUITEND geldig JSON:
        {
          "primary_keyword": "...",
          "secondary_keywords": ["...", "..."],
          "long_tail_keywords": ["...", "...", "..."],
          "related_topics": ["...", "..."],
          "content_clusters": ["...", "..."],
          "search_intent": "informatief|commercieel|transactioneel|navigatief",
          "target_audience": "...",
          "suggested_word_count": 1500,
          "faq_topics": ["Veel gestelde vraag 1?", "Vraag 2?", "Vraag 3?", "Vraag 4?", "Vraag 5?"]
        }
        PROMPT;
    }

    private function buildOutlinePrompt(string $keyword, array $research, string $siteName, string $brandContext, string $instructionLine, string $internalLinks, string $locale): string
    {
        $researchSummary = "Primair keyword: {$research['primary_keyword']}\n"
            . "Secundair: " . implode(', ', array_slice($research['secondary_keywords'] ?? [], 0, 5)) . "\n"
            . "Long-tail: " . implode(', ', array_slice($research['long_tail_keywords'] ?? [], 0, 5)) . "\n"
            . "Zoekintentie: " . ($research['search_intent'] ?? '') . "\n"
            . "Doelgroep: " . ($research['target_audience'] ?? '') . "\n"
            . "Aanbevolen woordenaantal: " . ($research['suggested_word_count'] ?? 1500);

        $internalLinksStr = $internalLinks ? "INTERNE PAGINA'S VOOR LINKBUILDING:\n{$internalLinks}" : '';

        return <<<PROMPT
        Je bent een SEO-specialist. Maak een gedetailleerde artikel opzet voor onderstaand zoekwoord.

        WEBSITE: {$siteName}
        TAAL: {$locale}
        {$brandContext}
        ZOEKWOORD: {$keyword}
        {$instructionLine}

        KEYWORD ONDERZOEK:
        {$researchSummary}

        {$internalLinksStr}

        Maak een volledige artikel structuur met:
        - Een sterke H1 die het primaire keyword bevat
        - 4-7 H2 secties die de content logisch opdelen
        - Per sectie optionele H3 subsecties
        - Notities per sectie over wat er behandeld moet worden
        - Een pakkende meta title (max 60 tekens) en meta description (max 155 tekens)
        - Een korte excerpt/samenvatting (2-3 zinnen) voor het artikel overzicht

        Retourneer UITSLUITEND geldig JSON:
        {
          "h1": "...",
          "meta_title": "... (max 60 tekens)",
          "meta_description": "... (max 155 tekens)",
          "excerpt": "2-3 zinnen samenvatting voor het artikel overzicht",
          "sections": [
            {
              "h2": "...",
              "h3s": ["...", "..."],
              "keywords_to_use": ["...", "..."],
              "content_notes": "Wat te behandelen in deze sectie, welke interne links relevant zijn",
              "estimated_words": 250
            }
          ]
        }
        PROMPT;
    }

    private function buildIntroPrompt(string $keyword, array $outline, array $research, string $siteName, string $brandContext, string $instructionLine, string $locale): string
    {
        $h1 = $outline['h1'] ?? $keyword;
        $sectionTitles = implode(', ', array_column($outline['sections'] ?? [], 'h2'));

        return <<<PROMPT
        Je bent een professionele content schrijver. Schrijf een pakkende introductie voor onderstaand artikel.

        WEBSITE: {$siteName}
        TAAL: {$locale}
        {$brandContext}
        {$instructionLine}

        ARTIKEL TITEL (H1): {$h1}
        PRIMAIR KEYWORD: {$keyword}
        ZOEKINTENTIE: {$research['search_intent']}
        DOELGROEP: {$research['target_audience']}
        ARTIKEL BEHANDELT: {$sectionTitles}

        Schrijf een introductie van 150-200 woorden die:
        - Direct aansluit bij de zoekintentie
        - Het primaire keyword in de eerste alinea bevat
        - De lezer motiveert om door te lezen
        - Kort aangeeft wat er behandeld wordt
        - Professioneel en vlot leesbaar is

        Retourneer UITSLUITEND geldig JSON:
        {
          "introduction": "<p>...</p><p>...</p>"
        }
        PROMPT;
    }

    private function buildSectionPrompt(string $keyword, array $outline, array $section, array $research, string $siteName, string $brandContext, string $instructionLine, string $internalLinks, string $locale): string
    {
        $h1 = $outline['h1'] ?? $keyword;
        $h2 = $section['h2'];
        $h3s = ! empty($section['h3s']) ? "H3 subsecties: " . implode(', ', $section['h3s']) : '';
        $notes = $section['content_notes'] ?? '';
        $keywords = ! empty($section['keywords_to_use']) ? "Gebruik deze zoekwoorden: " . implode(', ', $section['keywords_to_use']) : '';
        $estimatedWords = $section['estimated_words'] ?? 300;
        $internalLinksStr = $internalLinks ? "BESCHIKBARE INTERNE LINKS (gebruik er 0-2 per sectie waar relevant):\n{$internalLinks}" : '';

        return <<<PROMPT
        Je bent een professionele content schrijver. Schrijf de inhoud voor één sectie van een artikel.

        WEBSITE: {$siteName}
        TAAL: {$locale}
        {$brandContext}
        {$instructionLine}

        ARTIKEL TITEL (H1): {$h1}
        PRIMAIR KEYWORD: {$keyword}
        DOELGROEP: {$research['target_audience']}

        SECTIE TE SCHRIJVEN:
        H2: {$h2}
        {$h3s}
        Richtlijnen: {$notes}
        {$keywords}
        Streefwoorden: ±{$estimatedWords}

        {$internalLinksStr}

        Schrijf de volledige sectie inhoud als HTML. Start met de H2 tag.
        - Gebruik H3 tags voor subsecties als die opgegeven zijn
        - Verwerk zoekwoorden natuurlijk in de tekst
        - Voeg 0-2 interne links toe als <a href="url">ankertekst</a> waar dit relevant en natuurlijk is
        - Schrijf informatief, vlot leesbaar en afgestemd op de doelgroep
        - Geen extra uitleg of JSON wrapper — retourneer UITSLUITEND:

        {
          "content": "<h2>{$h2}</h2><p>...</p>"
        }
        PROMPT;
    }

    private function buildFaqPrompt(string $keyword, array $outline, array $research, string $siteName, string $brandContext, string $instructionLine, string $locale): string
    {
        $faqTopics = ! empty($research['faq_topics'])
            ? "Basis op deze vragen:\n" . implode("\n", array_map(fn ($q) => "- {$q}", $research['faq_topics']))
            : '';

        return <<<PROMPT
        Je bent een professionele content schrijver. Schrijf een FAQ sectie voor onderstaand artikel.

        WEBSITE: {$siteName}
        TAAL: {$locale}
        {$brandContext}
        {$instructionLine}

        ARTIKEL: {$outline['h1']}
        PRIMAIR KEYWORD: {$keyword}
        DOELGROEP: {$research['target_audience']}

        {$faqTopics}

        Schrijf 5-8 veelgestelde vragen met uitgebreide antwoorden (80-150 woorden per antwoord).
        Vragen moeten long-tail zoekwoorden bevatten en aansluiten bij de zoekintentie.
        Antwoorden mogen HTML bevatten (<p>, <strong>, <ul>, <li>).

        Retourneer UITSLUITEND geldig JSON:
        {
          "title": "Veelgestelde vragen over [onderwerp]",
          "subtitle": "Antwoorden op de meest gestelde vragen",
          "questions": [
            {
              "question": "...",
              "answer": "<p>...</p>"
            }
          ]
        }
        PROMPT;
    }

    private function buildConclusionPrompt(string $keyword, array $outline, array $research, string $siteName, string $brandContext, string $instructionLine, string $locale): string
    {
        $sectionTitles = implode(', ', array_column($outline['sections'] ?? [], 'h2'));

        return <<<PROMPT
        Je bent een professionele content schrijver. Schrijf een afsluitende conclusie voor onderstaand artikel.

        WEBSITE: {$siteName}
        TAAL: {$locale}
        {$brandContext}
        {$instructionLine}

        ARTIKEL: {$outline['h1']}
        PRIMAIR KEYWORD: {$keyword}
        BEHANDELDE ONDERWERPEN: {$sectionTitles}
        DOELGROEP: {$research['target_audience']}

        Schrijf een conclusie van 100-150 woorden die:
        - De belangrijkste punten kort samenvat
        - Het primaire keyword nogmaals vermeldt
        - Afsluit met een call-to-action of uitnodiging tot actie (contact, aankoop, meer lezen, etc.)

        Retourneer UITSLUITEND geldig JSON:
        {
          "conclusion": "<p>...</p><p>...</p>"
        }
        PROMPT;
    }

    private function releaseWithRateLimitMessage(ClaudeRateLimitException $e, string $step): void
    {
        $attempt = $this->attempts();
        $this->draft->setProgress("{$step} — rate limit bereikt (poging {$attempt}/{$this->tries}), wordt over ~1 minuut hervat.");
        $this->release(60);
    }
}

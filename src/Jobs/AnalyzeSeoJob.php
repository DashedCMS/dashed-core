<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\SeoVerbetervoorstel;

class AnalyzeSeoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public SeoVerbetervoorstel $voorstel,
        public string $locale,
        public string $instruction = '',
    ) {}

    public function handle(): void
    {
        $record = $this->voorstel->subject;

        if (! $record) {
            $this->voorstel->update([
                'status' => 'failed',
                'error_message' => 'Record niet gevonden.',
            ]);

            return;
        }

        try {
            $prompt = $this->buildPrompt($record);
            $result = ClaudeHelper::runJsonPrompt($prompt);

            if (! $result) {
                $this->voorstel->update([
                    'status' => 'failed',
                    'error_message' => 'Claude gaf geen geldig antwoord.',
                ]);

                return;
            }

            $this->voorstel->update([
                'status' => 'ready',
                'keyword_research' => $result['keyword_research'] ?? null,
                'analysis_summary' => $result['analysis_summary'] ?? null,
                'field_proposals' => $result['field_proposals'] ?? [],
            ]);
        } catch (\Throwable $e) {
            $this->voorstel->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function buildPrompt($record): string
    {
        $siteName = Customsetting::get('site_name') ?: config('app.name');
        $brandContext = ClaudeHelper::getBrandContext();
        $locale = $this->locale;

        // Gather URL
        $url = method_exists($record, 'getUrl') ? ($record->getUrl($locale) ?? '') : '';

        // Gather name/title
        $name = '';
        foreach (['name', 'title', 'slug'] as $field) {
            if (isset($record->$field)) {
                $value = is_array($record->$field)
                    ? ($record->$field[$locale] ?? $record->$field[array_key_first($record->$field)] ?? '')
                    : $record->$field;
                if ($value) {
                    $name = $value;
                    break;
                }
            }
        }
        if (method_exists($record, 'getTranslation')) {
            foreach (['name', 'title'] as $field) {
                try {
                    $val = $record->getTranslation($field, $locale);
                    if ($val) {
                        $name = $val;
                        break;
                    }
                } catch (\Throwable) {
                }
            }
        }

        // Gather metadata
        $metaTitle = '';
        $metaDescription = '';
        if ($record->metadata) {
            $metaTitle = $record->metadata->getTranslation('title', $locale) ?? '';
            $metaDescription = $record->metadata->getTranslation('description', $locale) ?? '';
        }

        // Optional extra instruction
        $instructionLine = $this->instruction
            ? "\nEXTRA INSTRUCTIE VAN DE GEBRUIKER: {$this->instruction}\n"
            : '';

        return <<<PROMPT
        Je bent een SEO-specialist voor een Nederlandse website. Analyseer de opgegeven pagina en geef concrete verbetervoorstellen.

        WEBSITE: {$siteName}
        {$brandContext}
        PAGINA URL: {$url}
        PAGINA NAAM: {$name}
        MODEL TYPE: {$record->getMorphClass()}
        {$instructionLine}

        HUIDIGE SEO GEGEVENS:
        - Meta title ({$this->charCount($metaTitle)} tekens): {$metaTitle}
        - Meta description ({$this->charCount($metaDescription)} tekens): {$metaDescription}

        LIMIETEN:
        - Meta title: maximaal 60 tekens
        - Meta description: maximaal 155 tekens

        Analyseer deze pagina en retourneer UITSLUITEND geldig JSON (geen markdown, geen extra tekst) in dit formaat:
        {
          "keyword_research": {
            "primary_keyword": "...",
            "secondary_keywords": ["...", "..."],
            "long_tail_keywords": ["...", "..."],
            "missing_opportunities": ["...", "..."]
          },
          "analysis_summary": "Markdown tekst met jouw analyse en aanbevelingen",
          "field_proposals": [
            {
              "field": "meta_title",
              "label": "Meta title",
              "current": "huidige waarde",
              "proposed": "verbeterde waarde (max 60 tekens)",
              "reason": "Korte uitleg waarom dit beter is",
              "improvement_type": "nieuw|herschrijven|aanscherpen|uitbreiden",
              "priority": "hoog|gemiddeld|laag",
              "accepted": null
            },
            {
              "field": "meta_description",
              "label": "Meta description",
              "current": "huidige waarde",
              "proposed": "verbeterde waarde (max 155 tekens)",
              "reason": "Korte uitleg waarom dit beter is",
              "improvement_type": "nieuw|herschrijven|aanscherpen|uitbreiden",
              "priority": "hoog|gemiddeld|laag",
              "accepted": null
            }
          ]
        }
        PROMPT;
    }

    private function charCount(string $str): int
    {
        return mb_strlen($str);
    }
}

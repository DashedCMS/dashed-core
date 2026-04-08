<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\SeoImprovement;
use Dashed\DashedCore\Exceptions\ClaudeRateLimitException;

class AnalyzeSeoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 10;
    public int $timeout = 600;

    public function backoff(): array
    {
        // Exponential backoff: 15s, 30s, 60s, 60s, ... for general failures
        return [15, 30, 60, 60, 60, 60, 60, 60, 60];
    }

    public function __construct(
        public SeoImprovement $voorstel,
        public string $locale,
        public string $instruction = '',
    ) {
    }

    public function failed(\Throwable $exception): void
    {
        $this->voorstel->update([
            'status' => 'failed',
            'progress_message' => null,
            'error_message' => $exception->getMessage(),
        ]);
    }

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

        $context = $this->buildPageContext($record);
        $totalBlocks = count($context['blocks']);
        $totalSteps = 1 + $totalBlocks; // step 1 + per-block

        // Step 1: keyword research + SEO field proposals
        $attempt = $this->attempts();
        $primaryKeyword = '';
        $progressPrefix = $attempt > 1 ? " (poging {$attempt}/{$this->tries})" : '';
        $this->voorstel->setProgress("Stap 1/{$totalSteps} — SEO velden en zoekwoorden analyseren...{$progressPrefix}");

        try {
            $seoResult = ClaudeHelper::runJsonPrompt(
                $this->buildSeoPrompt($context),
                maxTokens: 3000,
            );

            $primaryKeyword = $seoResult['keyword_research']['primary_keyword'] ?? '';

            $fieldProposals = array_values(array_filter(
                $seoResult['field_proposals'] ?? [],
                fn ($p) => ! str_contains(strtolower($p['field'] ?? ''), 'slug')
                    && trim((string) ($p['proposed'] ?? '')) !== trim((string) ($p['current'] ?? '')),
            ));

            $this->voorstel->update([
                'keyword_research' => $seoResult['keyword_research'] ?? null,
                'analysis_summary' => $seoResult['analysis_summary'] ?? null,
                'field_proposals' => $fieldProposals,
            ]);
        } catch (ClaudeRateLimitException $e) {
            $this->releaseWithRateLimitMessage($e);

            return;
        } catch (\Throwable $e) {
            // Let Laravel retry automatically — failed() is called only when retries are exhausted
            $this->voorstel->setProgress("Stap 1/{$totalSteps} — mislukt, opnieuw proberen... ({$e->getMessage()})");

            throw $e;
        }

        // Step 2: analyze each existing block individually
        $blockProposals = [];

        foreach ($context['blocks'] as $blockIndex => $block) {
            $blockType = $block['type'] ?? 'unknown';
            $stepNum = 2 + $blockIndex;
            $this->voorstel->setProgress("Stap {$stepNum}/{$totalSteps} — Blok '{$blockType}' analyseren...");

            try {
                $result = ClaudeHelper::runJsonPrompt(
                    $this->buildSingleBlockPrompt($context, $block, $blockIndex, $primaryKeyword),
                    maxTokens: 1500,
                );

                foreach ($result['block_proposals'] ?? [] as $proposal) {
                    $action = $proposal['action'] ?? 'update';
                    if ($action === 'update' && isset($proposal['field_updates'])) {
                        $flatCurrent = $this->flattenBlockData($block['data'] ?? []);
                        $rawData = $block['data'] ?? [];
                        $proposal['field_updates'] = array_filter(
                            $proposal['field_updates'],
                            function ($v, $path) use ($flatCurrent, $rawData) {
                                // Never touch numeric/boolean values
                                $original = data_get($rawData, str_replace('.', '.', $path));
                                if (is_int($original) || is_float($original) || is_bool($original)) {
                                    return false;
                                }
                                if (is_string($original) && is_numeric(trim($original))) {
                                    return false;
                                }

                                return trim(strip_tags((string) $v)) !== trim(strip_tags((string) ($flatCurrent[$path] ?? '')));
                            },
                            ARRAY_FILTER_USE_BOTH,
                        );
                        if (empty($proposal['field_updates'])) {
                            continue;
                        }
                    }
                    $blockProposals[] = $proposal;
                }
            } catch (ClaudeRateLimitException $e) {
                $this->releaseWithRateLimitMessage($e);

                return;
            } catch (\Throwable) {
                // Skip this block on failure, continue with others
            }
        }

        $this->voorstel->update([
            'status' => 'ready',
            'progress_message' => null,
            'block_proposals' => $blockProposals,
        ]);
    }

    private function releaseWithRateLimitMessage(ClaudeRateLimitException $e): void
    {
        $attempt = $this->attempts();
        $maxAttempts = $this->tries;

        $this->voorstel->update([
            'analysis_summary' => ($this->voorstel->fresh()->analysis_summary ?? '')
                . "\n\n> **Rate limit bereikt** (poging {$attempt}/{$maxAttempts}) — analyse wordt over ~1 minuut automatisch hervat.",
        ]);

        $this->release(60);
    }

    private function buildPageContext($record): array
    {
        $locale = $this->locale;

        $url = method_exists($record, 'getUrl') ? ($record->getUrl($locale) ?? '') : '';

        $name = '';
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
        if (! $name) {
            foreach (['name', 'title', 'slug'] as $field) {
                if (! empty($record->$field)) {
                    $val = is_array($record->$field)
                        ? ($record->$field[$locale] ?? reset($record->$field))
                        : $record->$field;
                    if ($val) {
                        $name = $val;

                        break;
                    }
                }
            }
        }

        $metaTitle = $record->metadata?->getTranslation('title', $locale) ?? '';
        $metaDescription = $record->metadata?->getTranslation('description', $locale) ?? '';

        $translatableLines = [];

        try {
            foreach ($record->translatable ?? [] as $field) {
                if (in_array($field, ['content', 'name', 'title', 'slug'])) {
                    continue;
                }

                try {
                    $val = $record->getTranslation($field, $locale);
                    if (is_string($val) && $val !== '') {
                        $translatableLines[] = "- {$field}: " . mb_substr(strip_tags($val), 0, 200);
                    }
                } catch (\Throwable) {
                }
            }
        } catch (\Throwable) {
        }

        $blocks = [];

        try {
            if (in_array('content', $record->translatable ?? [])) {
                $blocks = $record->getTranslation('content', $locale) ?: [];
            }
        } catch (\Throwable) {
        }

        // Collect internal URLs from all visitable models (for link building suggestions)
        $internalLinks = [];

        try {
            foreach (cms()->builder('routeModels') as $routeModel) {
                $class = $routeModel['class'];
                $nameField = $routeModel['nameField'] ?? 'name';
                $records = $class::limit(50)->get();
                foreach ($records as $r) {
                    if ($r->getKey() === $record->getKey() && get_class($r) === get_class($record)) {
                        continue;
                    }
                    $linkUrl = method_exists($r, 'getUrl') ? ($r->getUrl($locale) ?? '') : '';
                    if (! $linkUrl) {
                        continue;
                    }
                    $linkName = '';

                    try {
                        $linkName = method_exists($r, 'getTranslation') ? $r->getTranslation($nameField, $locale) : $r->$nameField;
                    } catch (\Throwable) {
                    }
                    if ($linkName) {
                        $internalLinks[] = "- {$linkName}: {$linkUrl}";
                    }
                }
            }
        } catch (\Throwable) {
        }

        return compact('url', 'name', 'metaTitle', 'metaDescription', 'translatableLines', 'blocks', 'record', 'locale', 'internalLinks');
    }

    private function buildSeoPrompt(array $ctx): string
    {
        $siteName = Customsetting::get('site_name') ?: config('app.name');
        $brandContext = ClaudeHelper::getBrandContext();
        $instructionLine = $this->instruction ? "\nEXTRA INSTRUCTIE: {$this->instruction}\n" : '';

        $translatableFieldsStr = $ctx['translatableLines']
            ? "OVERIGE VERTAALBARE VELDEN:\n" . implode("\n", $ctx['translatableLines'])
            : '';

        $blockSummary = $ctx['blocks']
            ? 'Huidige blokken op de pagina: ' . implode(', ', array_map(fn ($b) => $b['type'] ?? 'unknown', $ctx['blocks']))
            : '';

        $internalLinksStr = ! empty($ctx['internalLinks'])
            ? "BESCHIKBARE INTERNE PAGINA'S VOOR LINKBUILDING:\n" . implode("\n", array_slice($ctx['internalLinks'], 0, 40))
            : '';

        $metaTitle = $ctx['metaTitle'];
        $metaDescription = $ctx['metaDescription'];
        $url = $ctx['url'];
        $name = $ctx['name'];
        $morphClass = $ctx['record']->getMorphClass();

        return <<<PROMPT
        Je bent een SEO-specialist voor een Nederlandse website. Analyseer de pagina en geef verbetervoorstellen voor de SEO-velden.

        WEBSITE: {$siteName}
        {$brandContext}
        PAGINA URL: {$url}
        PAGINA NAAM: {$name}
        MODEL TYPE: {$morphClass}
        {$instructionLine}

        {$internalLinksStr}

        HUIDIGE SEO GEGEVENS:
        - Meta title ({$this->charCount($metaTitle)} tekens): {$metaTitle}
        - Meta description ({$this->charCount($metaDescription)} tekens): {$metaDescription}

        LIMIETEN:
        - Meta title: maximaal 60 tekens
        - Meta description: maximaal 155 tekens

        {$translatableFieldsStr}

        {$blockSummary}

        BELANGRIJK: stel NOOIT wijzigingen voor aan slug-velden. Slugs mogen nooit worden aangepast.
        Gebruik de beschikbare interne pagina's om interne links voor te stellen in de analysis_summary waar relevant.

        Indien er content blokken ontbreken die de SEO of inhoud zouden verbeteren, vermeld deze dan als aanbeveling in de analysis_summary onder een kopje "## Handmatig toe te voegen" — dit zijn uitsluitend tekstuele suggesties, geen automatische aanpassingen.

        Beoordeel daarnaast of FAQ-inhoud relevant is voor deze pagina (bijv. voor long-tail keywords, veelgestelde vragen over het onderwerp). Als dat zo is, bedenk dan 3-6 concrete vragen én antwoorden die passen bij het primaire keyword en de pagina-inhoud, en voeg deze toe onder "## Handmatig toe te voegen" als:
        ### FAQ suggesties
        **V: [vraag]**
        A: [antwoord]
        Als FAQ niet relevant is voor deze pagina, laat dit kopje dan weg.

        Retourneer UITSLUITEND geldig JSON (geen markdown):
        {
          "keyword_research": {
            "primary_keyword": "...",
            "secondary_keywords": ["..."],
            "long_tail_keywords": ["..."],
            "missing_opportunities": ["..."]
          },
          "analysis_summary": "Markdown samenvatting van je bevindingen",
          "field_proposals": [
            {
              "field": "meta_title",
              "label": "Meta title",
              "current": "huidige waarde",
              "proposed": "verbeterde waarde (max 60 tekens)",
              "reason": "Korte uitleg",
              "improvement_type": "nieuw|herschrijven|aanscherpen|uitbreiden",
              "priority": "hoog|gemiddeld|laag",
              "accepted": null
            }
          ]
        }
        PROMPT;
    }

    private function buildSingleBlockPrompt(array $ctx, array $block, int $blockIndex, string $primaryKeyword): string
    {
        $siteName = Customsetting::get('site_name') ?: config('app.name');
        $brandContext = ClaudeHelper::getBrandContext();
        $instructionLine = $this->instruction ? "\nEXTRA INSTRUCTIE: {$this->instruction}\n" : '';

        $blockType = $block['type'] ?? 'unknown';
        $data = $block['data'] ?? [];
        $url = $ctx['url'];
        $name = $ctx['name'];
        $internalLinksStr = ! empty($ctx['internalLinks'])
            ? "BESCHIKBARE INTERNE PAGINA'S VOOR LINKBUILDING:\n" . implode("\n", array_slice($ctx['internalLinks'], 0, 30))
            : '';

        $contentLines = [];
        $exampleUpdates = [];

        foreach ($data as $field => $value) {
            if (is_string($value)) {
                if (trim($value) === '' || is_numeric(trim($value)) || is_bool($value)) {
                    continue;
                }
                if ($this->isHtmlContent($value)) {
                    // HTML field: send raw, return raw with same structure
                    $contentLines[] = "\n{$field} [HTML — behoud structuur, verbeter alleen tekst en voeg eventueel interne links toe]:\n{$value}";
                    $exampleUpdates[] = "        \"{$field}\": \"<p>Verbeterde HTML met zelfde structuur</p>\"";
                } else {
                    $contentLines[] = "{$field}: \"" . mb_substr($value, 0, 300) . "\"";
                    $exampleUpdates[] = "        \"{$field}\": \"verbeterde waarde\"";
                }
            } elseif (is_int($value) || is_float($value) || is_bool($value)) {
                // Numeric/boolean fields: never touch these
                continue;
            } elseif (is_array($value)) {
                // Repeater: one level deep only (also handle empty repeaters)
                $subFieldNames = $this->getRepeaterSubFields($blockType, $field, $value);
                if (empty($subFieldNames)) {
                    continue;
                }

                $contentLines[] = "\n{$field} (" . count($value) . " items):";
                foreach ($value as $idx => $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $itemParts = [];
                    foreach ($subFieldNames as $subField) {
                        $subValue = $item[$subField] ?? '';
                        if (! is_string($subValue) || is_numeric(trim($subValue))) {
                            continue;
                        }
                        if ($this->isHtmlContent($subValue)) {
                            $itemParts[] = "  {$field}.{$idx}.{$subField} [HTML — behoud structuur]:\n    " . (trim($subValue) === '' ? '(leeg)' : $subValue);
                            $exampleUpdates[] = "        \"{$field}.{$idx}.{$subField}\": \"<p>Verbeterde HTML</p>\"";
                        } else {
                            $display = trim($subValue) === '' ? '(leeg)' : mb_substr($subValue, 0, 200);
                            $itemParts[] = "  {$field}.{$idx}.{$subField}: \"{$display}\"";
                            $exampleUpdates[] = "        \"{$field}.{$idx}.{$subField}\": \"verbeterde waarde\"";
                        }
                    }
                    if ($itemParts) {
                        $contentLines[] = implode("\n", $itemParts);
                    }
                }
                // Always show next index so Claude can add items
                $nextIdx = count($value);
                foreach ($subFieldNames as $subField) {
                    $exampleUpdates[] = "        \"{$field}.{$nextIdx}.{$subField}\": \"nieuw item\"";
                }
            }
        }

        if (empty($contentLines)) {
            return '';
        }

        $blockContent = "BLOK {$blockIndex} (type: {$blockType}):\n" . implode("\n", $contentLines);
        $exampleJson = implode(",\n", array_unique($exampleUpdates));

        return <<<PROMPT
        Je bent een SEO-specialist voor een Nederlandse website. Beoordeel en verbeter de inhoud van het onderstaande content blok.

        WEBSITE: {$siteName}
        {$brandContext}
        PAGINA URL: {$url}
        PAGINA NAAM: {$name}
        PRIMAIR KEYWORD: {$primaryKeyword}
        {$instructionLine}

        {$internalLinksStr}

        {$blockContent}

        Beoordeel:
        1. Is de inhoud relevant en SEO-geoptimaliseerd voor deze pagina?
        2. Kunnen teksten sterker, keyword-rijker of completer?
        3. Moeten er extra items/vragen/antwoorden worden toegevoegd (bij repeaters)?

        Retourneer UITSLUITEND geldig JSON (geen markdown):
        {
          "block_proposals": [
            {
              "action": "update",
              "block_index": {$blockIndex},
              "block_type": "{$blockType}",
              "label": "Leesbare beschrijving",
              "reason": "Waarom deze wijzigingen verbeteren",
              "field_updates": {
        {$exampleJson}
              },
              "priority": "hoog|gemiddeld|laag",
              "accepted": null
            }
          ]
        }

        INSTRUCTIES:
        - Geef alleen een update-voorstel als er een duidelijke verbetering mogelijk is
        - Pas alleen de velden aan die hierboven zijn weergegeven — geen andere velden toevoegen
        - HTML-velden: stuur EXACTE HTML terug met dezelfde tags en structuur — verbeter alleen de tekst en voeg eventueel <a href="url">interne links</a> toe
        - Voeg bij repeater-velden nieuwe items toe door het volgende indexnummer te gebruiken
        - Stel nooit nieuwe blokken voor — alleen updates op bestaande blokken
        - Als niets verbeterd hoeft te worden, retourneer: {"block_proposals": []}
        PROMPT;
    }

    private function isHtmlContent(string $value): bool
    {
        return (bool) preg_match('/<[a-z][^>]*>/i', $value);
    }

    private function flattenBlockData(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $path = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;
            if (is_string($value) && trim(strip_tags($value)) !== '' && ! is_numeric(trim($value))) {
                $result[$path] = mb_substr(strip_tags($value), 0, 300);
            } elseif (is_array($value)) {
                $result = array_merge($result, $this->flattenBlockData($value, $path));
            }
        }

        return $result;
    }

    /**
     * Get the sub-field names for a repeater field.
     * Falls back to introspecting the block schema when the repeater is empty.
     */
    private function getRepeaterSubFields(string $blockType, string $field, array $items): array
    {
        // Derive from existing data first
        foreach ($items as $item) {
            if (is_array($item) && ! empty($item)) {
                return array_values(array_filter(array_keys($item), fn ($k) => is_string($k)));
            }
        }

        // Repeater is empty — introspect the block schema
        try {
            cms()->activateBuilderBlockClasses();
            foreach (cms()->builder('blocks') as $block) {
                if ($block->getName() !== $blockType) {
                    continue;
                }
                foreach ($block->getChildComponents() as $component) {
                    if (! method_exists($component, 'getName') || $component->getName() !== $field) {
                        continue;
                    }
                    $subFields = [];
                    foreach ($component->getChildComponents() as $sub) {
                        if (! method_exists($sub, 'getName') || ! $sub->getName()) {
                            continue;
                        }
                        $type = class_basename(get_class($sub));
                        if (str_contains($type, 'FileUpload') || str_contains($type, 'SpatieMedia')
                            || str_contains($type, 'Toggle') || str_contains($type, 'Checkbox')
                            || str_contains($type, 'Hidden') || str_contains($type, 'Placeholder')) {
                            continue;
                        }
                        $subFields[] = $sub->getName();
                    }

                    return $subFields;
                }
            }
        } catch (\Throwable) {
        }

        return [];
    }

    private function charCount(string $str): int
    {
        return mb_strlen($str);
    }
}

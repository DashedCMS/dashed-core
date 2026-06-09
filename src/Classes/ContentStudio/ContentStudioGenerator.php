<?php

namespace Dashed\DashedCore\Classes\ContentStudio;

use Dashed\DashedAi\Facades\Ai;

class ContentStudioGenerator
{
    /**
     * @param  array<int, array>  $catalog
     * @return array<int, array{type: string, data: array}>
     */
    public function generate(string $brief, array $catalog, string $locale): array
    {
        $prompt = $this->buildPrompt($brief, $catalog, $locale);

        $result = Ai::json($prompt);

        $blocks = is_array($result) ? ($result['blocks'] ?? $result) : null;

        return $this->normalize($blocks, $catalog);
    }

    public function buildPrompt(string $brief, array $catalog, string $locale): string
    {
        $catalogJson = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
            Je bent een ervaren webredacteur. Stel op basis van de opdracht een complete pagina samen
            uit de beschikbare content-blocks. Schrijf alle teksten in de taal met locale-code "{$locale}".

            Opdracht:
            {$brief}

            Beschikbare blocks (JSON, met per block de toegestane velden en types):
            {$catalogJson}

            Regels:
            - Gebruik uitsluitend block-types en veldnamen uit de lijst.
            - Voor velden van type "image": geef GEEN id, maar een korte beschrijvende beeldprompt
              (waar het beeld over moet gaan), in het Nederlands.
            - Voor velden van type "repeater": geef een lijst van rijen met de geneste velden uit "of".
            - Vul alleen velden die je zinvol kunt invullen.

            Antwoord met JSON in exact deze vorm:
            {"blocks": [{"type": "<block-type>", "data": { ...velden... }}, ...]}
            PROMPT;
    }

    /**
     * @param  mixed  $aiBlocks
     * @param  array<int, array>  $catalog
     * @return array<int, array{type: string, data: array}>
     */
    public function normalize(mixed $aiBlocks, array $catalog): array
    {
        if (! is_array($aiBlocks)) {
            return [];
        }

        $byType = [];
        foreach ($catalog as $entry) {
            if (isset($entry['type'])) {
                $byType[$entry['type']] = $entry['fields'] ?? [];
            }
        }

        $blocks = [];

        foreach ($aiBlocks as $block) {
            if (! is_array($block) || ! isset($block['type']) || ! isset($byType[$block['type']])) {
                continue;
            }

            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            $blocks[] = [
                'type' => $block['type'],
                'data' => $this->coerceData($data, $byType[$block['type']]),
            ];
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array>  $fields
     */
    private function coerceData(array $data, array $fields): array
    {
        $clean = [];
        $imagePrompts = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            if (! array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];
            $type = $field['type'] ?? null;

            if ($type === 'image') {
                if (is_string($value) && trim($value) !== '') {
                    $imagePrompts[$name] = trim($value);
                }

                continue;
            }

            if ($type === 'repeater') {
                $clean[$name] = $this->coerceRepeater($value, $field['of'] ?? []);

                continue;
            }

            $clean[$name] = $value;
        }

        if ($imagePrompts !== []) {
            $clean['_ai_image_prompt'] = $imagePrompts;
        }

        return $clean;
    }

    /**
     * @param  mixed  $value
     * @param  array<int, array>  $of
     * @return array<int, array>
     */
    private function coerceRepeater(mixed $value, array $of): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];
        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rows[] = $this->coerceData($row, $of);
        }

        return $rows;
    }
}

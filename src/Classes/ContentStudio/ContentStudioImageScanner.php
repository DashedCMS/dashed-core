<?php

namespace Dashed\DashedCore\Classes\ContentStudio;

class ContentStudioImageScanner
{
    /**
     * @param  array<int|string, array>  $blocks
     * @return array<int, array{block_key: int|string, field: string, prompt: string}>
     */
    public function pending(array $blocks): array
    {
        $pending = [];

        foreach ($blocks as $key => $block) {
            $data = $block['data'] ?? null;
            if (! is_array($data)) {
                continue;
            }

            $prompts = $data['_ai_image_prompt'] ?? null;
            if (! is_array($prompts)) {
                continue;
            }

            foreach ($prompts as $field => $prompt) {
                if (! is_string($field) || ! is_string($prompt) || trim($prompt) === '') {
                    continue;
                }

                $current = $data[$field] ?? null;
                if ($current !== null && $current !== '' && $current !== []) {
                    continue;
                }

                $pending[] = [
                    'block_key' => $key,
                    'field' => $field,
                    'prompt' => $prompt,
                ];
            }
        }

        return $pending;
    }
}

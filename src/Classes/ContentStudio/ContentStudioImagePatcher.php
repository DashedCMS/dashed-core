<?php

namespace Dashed\DashedCore\Classes\ContentStudio;

class ContentStudioImagePatcher
{
    public const HANDLER = 'content_studio_image';

    /**
     * @param  array<int|string, array>  $blocks
     * @return array<int|string, array>
     */
    public function patch(array $blocks, int|string $blockKey, string $field, string $expectedPrompt, int $mediaId): array
    {
        // $blockKey may arrive as a numeric string when round-tripped through the JSON
        // context column; PHP canonicalizes numeric string array keys, so int and "int" both match.
        if (! array_key_exists($blockKey, $blocks)) {
            return $blocks;
        }

        $data = $blocks[$blockKey]['data'] ?? null;
        if (! is_array($data)) {
            return $blocks;
        }

        $storedPrompt = $data['_ai_image_prompt'][$field] ?? null;
        if ($storedPrompt !== $expectedPrompt) {
            return $blocks;
        }

        $current = $data[$field] ?? null;
        if ($current !== null && $current !== '' && $current !== []) {
            return $blocks;
        }

        $data[$field] = $mediaId;

        unset($data['_ai_image_prompt'][$field]);
        if (($data['_ai_image_prompt'] ?? []) === []) {
            unset($data['_ai_image_prompt']);
        }

        $blocks[$blockKey]['data'] = $data;

        return $blocks;
    }
}

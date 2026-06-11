<?php

namespace Dashed\DashedCore\Services\VisualEditor;

class VisitableBlockContentPatcher
{
    /**
     * Zet blocks[$blockIndex]['data'][$field] = $value, mits dat blok en dat veld
     * al bestaan. Anders ongewijzigd. Gooit nooit.
     *
     * @param  array<int, array>  $blocks
     * @return array<int, array>
     */
    public function patch(array $blocks, int $blockIndex, string $field, string $value): array
    {
        if (! array_key_exists($blockIndex, $blocks)) {
            return $blocks;
        }

        $data = $blocks[$blockIndex]['data'] ?? null;
        if (! is_array($data) || ! array_key_exists($field, $data)) {
            return $blocks;
        }

        $data[$field] = $value;
        $blocks[$blockIndex]['data'] = $data;

        return $blocks;
    }
}

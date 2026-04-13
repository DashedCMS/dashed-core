<?php

namespace Dashed\DashedCore\Classes;

class WebsiteContentCollector
{
    /**
     * Collect content samples from all registered visitable models (routeModels),
     * including metadata and content block text. Used as website context for AI prompts.
     */
    public static function collect(): string
    {
        $parts = [];
        $locale = app()->getLocale();

        foreach (cms()->builder('routeModels') as $routeModel) {
            $class = $routeModel['class'];
            $nameField = $routeModel['nameField'] ?? 'name';

            try {
                $records = $class::with(['metadata', 'customBlocks'])->limit(10)->get();
            } catch (\Throwable) {
                continue;
            }

            $lines = [];
            foreach ($records as $record) {
                $name = '';

                try {
                    $name = method_exists($record, 'getTranslation')
                        ? $record->getTranslation($nameField, $locale)
                        : $record->$nameField;
                } catch (\Throwable) {
                }

                $metaTitle = $record->metadata?->getTranslation('title', $locale) ?? '';
                $metaDesc = $record->metadata?->getTranslation('description', $locale) ?? '';

                $blockText = '';

                try {
                    $blocks = $record->customBlocks?->getTranslation('blocks', $locale);
                    if ($blocks) {
                        $blockText = static::extractTextFromBlocks($blocks);
                    }
                } catch (\Throwable) {
                }

                $line = "- {$name}";
                if ($metaTitle) {
                    $line .= " | Meta: {$metaTitle}";
                }
                if ($metaDesc) {
                    $line .= " | {$metaDesc}";
                }
                if ($blockText) {
                    $line .= "\n  Inhoud: " . mb_substr($blockText, 0, 300);
                }

                if ($name || $metaTitle) {
                    $lines[] = $line;
                }
            }

            if ($lines) {
                $parts[] = "{$routeModel['pluralName']}:\n" . implode("\n", $lines);
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Recursively extract plain text from content block data.
     */
    protected static function extractTextFromBlocks(array $blocks): string
    {
        $textFields = ['title', 'subtitle', 'content', 'text', 'description', 'body'];
        $texts = [];

        foreach ($blocks as $block) {
            $data = $block['data'] ?? $block;
            if (! is_array($data)) {
                continue;
            }

            foreach ($textFields as $field) {
                if (isset($data[$field]) && is_string($data[$field]) && trim(strip_tags($data[$field])) !== '') {
                    $texts[] = trim(strip_tags($data[$field]));
                }
            }

            foreach (['blocks', 'items', 'columns'] as $nested) {
                if (isset($data[$nested]) && is_array($data[$nested])) {
                    $nestedText = static::extractTextFromBlocks($data[$nested]);
                    if ($nestedText) {
                        $texts[] = $nestedText;
                    }
                }
            }
        }

        return implode(' ', $texts);
    }
}

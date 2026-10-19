<?php

namespace Dashed\DashedCore\Classes\Seo;

use Illuminate\Support\Str;

/**
 * Terugval voor de meta-omschrijving, eerste niet-lege wint: wat er al
 * gezet is, een korte omschrijving op het model, tekst uit de
 * inhoudsblokken, de site-standaard. Leeg blijft leeg; de head rendert dan
 * geen tag.
 */
class MetaDescription
{
    public const MAX = 155;

    protected const SKIPPED_KEYS = '/(^|_)(image|images|url|link|href|id|ids|icon|video|file|slug|color|class|type|style)($|_)/i';

    public static function for(string $current, ?object $model, string $default): string
    {
        if (trim($current) !== '') {
            return trim($current);
        }

        foreach (['short_description', 'excerpt', 'description'] as $attribute) {
            $text = $model ? self::clean(self::attribute($model, $attribute)) : '';
            if ($text !== '') {
                return Str::limit($text, self::MAX, '…');
            }
        }

        $blocks = $model ? self::attribute($model, 'content') : null;
        if (is_array($blocks)) {
            $text = self::textFromBlocks($blocks);
            if ($text !== '') {
                return $text;
            }
        }

        return trim($default);
    }

    public static function textFromBlocks(array $blocks, int $max = self::MAX): string
    {
        $parts = [];
        self::collect($blocks, $parts);
        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)));

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space ? mb_substr($cut, 0, $space) : $cut, ' ,.;:') . '…';
    }

    protected static function collect(array $node, array &$parts): void
    {
        if (in_array($node['type'] ?? null, ['doc', 'paragraph'], true) || isset($node['text'])) {
            self::collectTipTap($node, $parts);

            return;
        }

        foreach ($node as $key => $value) {
            if (is_string($key) && preg_match(self::SKIPPED_KEYS, $key)) {
                continue;
            }

            if (is_array($value)) {
                self::collect($value, $parts);
            } elseif (is_string($value)) {
                $text = self::clean($value);
                if ($text !== '' && ! preg_match('#^(https?:)?/#', $text)) {
                    $parts[] = $text;
                }
            }
        }
    }

    protected static function collectTipTap(array $node, array &$parts): void
    {
        if (isset($node['text']) && is_string($node['text'])) {
            $parts[] = trim($node['text']);
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                self::collectTipTap($child, $parts);
            }
        }
    }

    protected static function attribute(object $model, string $name): mixed
    {
        try {
            return $model->{$name} ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function clean(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];
            self::collectTipTap($value, $parts);
            $value = implode(' ', $parts);
        }

        if (! is_string($value)) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value))));
    }
}

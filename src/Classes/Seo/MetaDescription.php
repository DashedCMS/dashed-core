<?php

namespace Dashed\DashedCore\Classes\Seo;

/**
 * Terugval voor de meta-omschrijving, eerste niet-lege wint: wat er al
 * gezet is, een korte omschrijving op het model, tekst uit de
 * inhoudsblokken, de site-standaard. Leeg blijft leeg; de head rendert dan
 * geen tag.
 *
 * Uit een blok worden alleen bekende tekstvelden gelezen (TEXT_KEYS), nooit
 * instellingen, id's of kiezerwaarden; een veld is een TipTap-document als
 * het type 'doc' heeft. Binnen een alinea worden tekstknopen aaneengeplakt
 * (een vet woorddeel is geen nieuw woord), alinea's gescheiden door een
 * spatie.
 */
class MetaDescription
{
    public const MAX = 155;

    public const TEXT_KEYS = ['title', 'heading', 'subtitle', 'intro', 'content', 'text', 'description', 'excerpt'];

    public static function for(string $current, ?object $model, string $default): string
    {
        if (trim($current) !== '') {
            return trim($current);
        }

        foreach (['short_description', 'excerpt', 'description'] as $attribute) {
            $text = $model ? self::clean(self::attribute($model, $attribute)) : '';
            if ($text !== '') {
                return self::limit($text, self::MAX);
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

        return self::limit(trim(preg_replace('/\s+/u', ' ', implode(' ', array_filter($parts, fn ($p) => $p !== '')))), $max);
    }

    protected static function limit(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space ? mb_substr($cut, 0, $space) : $cut, ' ,.;:') . '…';
    }

    protected static function collect(array $node, array &$parts): void
    {
        if (($node['type'] ?? null) === 'doc') {
            $parts[] = self::tipTapText($node);

            return;
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                if (is_string($key) && $key !== 'data' && ! in_array($key, self::TEXT_KEYS, true) && ($value['type'] ?? null) !== 'doc' && ! self::isList($value)) {
                    // Een genest instellingenobject (een link, een knop) is geen tekst;
                    // 'data' is de inhoud van een blok en een lijst zijn de items erin.
                    continue;
                }
                self::collect($value, $parts);
            } elseif (is_string($value) && is_string($key) && in_array($key, self::TEXT_KEYS, true)) {
                $parts[] = self::clean($value);
            }
        }
    }

    /**
     * Een lijst van blokken of van geneste items (numerieke sleutels) wordt
     * altijd doorlopen; een object met benoemde sleutels alleen als het zelf
     * een tekstveld is of een document.
     */
    protected static function isList(array $value): bool
    {
        return array_is_list($value);
    }

    protected static function tipTapText(array $node): string
    {
        if (isset($node['text']) && is_string($node['text'])) {
            return $node['text'];
        }

        $children = [];
        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $children[] = self::tipTapText($child);
            }
        }

        // Inline knopen (tekst met marks) plakken aaneen; blokknopen krijgen een spatie.
        $inline = isset($node['type']) && in_array($node['type'], ['paragraph', 'heading', 'listItem', 'blockquote'], true);

        return $inline ? implode('', $children) : implode(' ', $children);
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
            $value = ($value['type'] ?? null) === 'doc' ? self::tipTapText($value) : '';
        }

        if (! is_string($value)) {
            return '';
        }

        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5)));
    }
}

<?php

namespace Dashed\DashedCore\Filament\Components;

use Illuminate\Support\Str;

/**
 * De kop van een ingeklapt blok: zonder samenvatting ziet een redacteur alleen
 * een rij "Tekst, Tekst, Tekst" en weet niemand welk blok welk is.
 */
class BlockHeaderLabel
{
    protected const PREFERRED_KEYS = ['title', 'heading', 'name'];

    protected const SKIPPED_KEY_PATTERN = '/(^type$|_type$|^url$|_url$|_id$|^globalBlock$|image|video|icon|color|class|variant)/i';

    protected const MAX_LENGTH = 60;

    public static function for(string $base, ?array $state, ?int $index, ?string $summary = null): string
    {
        $label = $index === null ? $base : ($index + 1) . ". {$base}";
        $summary ??= static::summary($state);

        return filled($summary) ? "{$label}: {$summary}" : $label;
    }

    public static function summary(?array $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        foreach (static::PREFERRED_KEYS as $key) {
            if (filled($text = static::clean($state[$key] ?? null))) {
                return $text;
            }
        }

        foreach ($state as $key => $value) {
            if (is_string($key) && preg_match(static::SKIPPED_KEY_PATTERN, $key)) {
                continue;
            }

            if (filled($text = static::clean($value)) && ! str_starts_with($text, 'http')) {
                return $text;
            }
        }

        return null;
    }

    protected static function clean(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = ($value['type'] ?? null) === 'doc'
                ? static::firstTipTapNodeText($value['content'] ?? [])
                : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value))));

        return $text === '' ? null : Str::limit($text, static::MAX_LENGTH, '…');
    }

    /**
     * cms()->editorField() slaat een RichEditor op als TipTap-document, geen
     * kale HTML-string. De eerste blokknoop (alinea, kop, ...) die tekst
     * oplevert is de samenvatting; een lege alinea (vaak het eerste blok na
     * het legen van een editor) slaat door naar de volgende knoop.
     *
     * @param  array<int, mixed>  $nodes
     */
    protected static function firstTipTapNodeText(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            if (is_array($node) && filled($text = static::tiptapNodeText($node))) {
                return $text;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function tiptapNodeText(array $node): string
    {
        $text = is_string($node['text'] ?? null) ? $node['text'] : '';

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $text .= static::tiptapNodeText($child);
            }
        }

        return $text;
    }
}

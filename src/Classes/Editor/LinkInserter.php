<?php

namespace Dashed\DashedCore\Classes\Editor;

/**
 * Zet één link in blokinhoud: het eerste voorkomen van de ankertekst dat nog
 * geen link is, als hele woorden en zonder op hoofdletters te letten. Werkt op
 * de twee vormen waarin de rich editor tekst opslaat: een Tiptap-document
 * (tekstknoop krijgt een link-mark, zie ContentLinkRewriter) en HTML (tekst
 * buiten tags en buiten een bestaande <a>). Slaat niets op; geeft de nieuwe
 * inhoud terug, of null als de tekst nergens (meer) los staat.
 */
class LinkInserter
{
    /** Sleutels die nooit lopende tekst zijn. */
    private const SKIP_KEYS = ['type', 'url', 'href', 'link', 'id', 'slug', 'image', 'images', 'icon', 'class', 'style'];

    protected bool $done = false;

    public function __construct(
        protected string $anchor,
        protected string $url,
    ) {
    }

    public static function insert(array $content, string $anchor, string $url): ?array
    {
        $anchor = trim($anchor);
        if ($anchor === '' || $url === '') {
            return null;
        }

        $inserter = new static($anchor, $url);
        $result = $inserter->walk($content);

        return $inserter->done ? $result : null;
    }

    protected function pattern(): string
    {
        return '/(?<![\p{L}\p{N}])'.preg_quote($this->anchor, '/').'(?![\p{L}\p{N}])/iu';
    }

    protected function walk(mixed $value, ?string $key = null): mixed
    {
        if ($this->done) {
            return $value;
        }

        if (is_array($value)) {
            if (($value['type'] ?? null) === 'doc' && isset($value['content']) && is_array($value['content'])) {
                $value['content'] = $this->walkTiptapChildren($value['content']);

                return $value;
            }

            foreach ($value as $k => $child) {
                if (is_string($k) && in_array($k, self::SKIP_KEYS, true)) {
                    continue;
                }
                $value[$k] = $this->walk($child, is_string($k) ? $k : $key);
                if ($this->done) {
                    break;
                }
            }

            return $value;
        }

        if (is_string($value) && $value !== '' && ! in_array($key, self::SKIP_KEYS, true) && ! str_ends_with((string) $key, '_url')) {
            return $this->insertInHtml($value);
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return array<int, mixed>
     */
    protected function walkTiptapChildren(array $nodes): array
    {
        $out = [];

        foreach ($nodes as $node) {
            if ($this->done || ! is_array($node)) {
                $out[] = $node;

                continue;
            }

            if (($node['type'] ?? null) === 'text' && is_string($node['text'] ?? null) && ! $this->hasLinkMark($node['marks'] ?? [])) {
                if (preg_match($this->pattern(), $node['text'], $m, PREG_OFFSET_CAPTURE)) {
                    [$found, $offset] = $m[0];
                    $before = substr($node['text'], 0, $offset);
                    $after = substr($node['text'], $offset + strlen($found));
                    $marks = $node['marks'] ?? [];

                    if ($before !== '') {
                        $out[] = array_merge($node, ['text' => $before]);
                    }
                    $out[] = array_merge($node, [
                        'text' => $found,
                        'marks' => array_merge($marks, [['type' => 'link', 'attrs' => ['href' => $this->url, 'target' => null, 'rel' => null]]]),
                    ]);
                    if ($after !== '') {
                        $out[] = array_merge($node, ['text' => $after]);
                    }

                    $this->done = true;

                    continue;
                }
            }

            if (isset($node['content']) && is_array($node['content'])) {
                $node['content'] = $this->walkTiptapChildren($node['content']);
            }

            $out[] = $node;
        }

        return $out;
    }

    protected function hasLinkMark(mixed $marks): bool
    {
        return is_array($marks) && collect($marks)->contains(fn ($mark) => is_array($mark) && ($mark['type'] ?? null) === 'link');
    }

    /**
     * Alleen tekst die buiten tags en buiten een <a> staat. Platte tekst
     * zonder tags (een gewoon tekstveld) krijgt geen link: daar rendert het
     * thema geen HTML.
     */
    protected function insertInHtml(string $html): string
    {
        if (! str_contains($html, '<')) {
            return $html;
        }

        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $insideLink = 0;

        foreach ($parts as $i => $part) {
            if (str_starts_with($part, '<')) {
                if (preg_match('/^<a[\s>]/i', $part)) {
                    $insideLink++;
                } elseif (preg_match('/^<\/a\s*>/i', $part)) {
                    $insideLink = max(0, $insideLink - 1);
                }

                continue;
            }

            if ($insideLink > 0) {
                continue;
            }

            $replaced = preg_replace_callback(
                $this->pattern(),
                fn (array $m) => '<a href="'.e($this->url).'">'.$m[0].'</a>',
                $part,
                1,
                $count,
            );

            if ($count > 0) {
                $parts[$i] = $replaced;
                $this->done = true;

                return implode('', $parts);
            }
        }

        return $html;
    }
}

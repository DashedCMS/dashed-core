<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Tiptap\Core\Node;

class ExternalVideoExtension extends Node
{
    public static $name = 'externalVideo';
    public static $priority = 100;

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [
                'class' => 'external-video',
                'style' => 'aspect-ratio:16/9;width:100%;max-width:100%;height:auto;border:0;display:block;',
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [['tag' => 'div.responsive iframe.external-video']];
    }

    public function addAttributes(): array
    {
        return [
            'src' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('src'),
                'renderHTML' => fn ($attrs) => ['src' => $attrs->src ?? null],
            ],
            'type' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('data-type') ?? 'auto',
                'renderHTML' => fn ($attrs) => ['data-type' => $attrs->type ?? 'auto'],
            ],
            'ratio' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('data-ratio') ?? '16:9',
                'renderHTML' => fn ($attrs) => ['data-ratio' => $attrs->ratio ?? '16:9'],
            ],
            'maxWidth' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('data-max-width') ?? '100',
                'renderHTML' => fn ($attrs) => ['data-max-width' => $attrs->maxWidth ?? '100'],
            ],
            'widthUnit' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('data-width-unit') ?? '%',
                'renderHTML' => fn ($attrs) => ['data-width-unit' => $attrs->widthUnit ?? '%'],
            ],
        ];
    }

    /**
     * Zet een willekeurige video-URL om naar een embeddable variant.
     */
    protected function getEmbedUrl(?string $src, ?string $type = 'auto'): string
    {
        if (! $src) {
            return '';
        }

        // al embed? laat staan
        if (str_contains($src, '/embed/')) {
            return $src;
        }

        preg_match('/[?&]v=([^&]+)/', $src, $yt);
        preg_match('/youtu\.be\/([^?]+)/', $src, $ytShort);
        preg_match('/vimeo\.com\/(\d+)/', $src, $vimeo);
        preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $src, $file);

        // autodetect
        if ($type === 'auto' || ! $type) {
            if (! empty($yt[1]) || ! empty($ytShort[1])) {
                $type = 'youtube';
            } elseif (! empty($vimeo[1])) {
                $type = 'vimeo';
            } elseif (! empty($file[1])) {
                $type = 'mp4';
            } else {
                $type = 'unknown';
            }
        }

        return match ($type) {
            'youtube' => isset($yt[1]) || isset($ytShort[1])
                ? 'https://www.youtube-nocookie.com/embed/' . ($yt[1] ?? $ytShort[1])
                : $src,
            'vimeo' => isset($vimeo[1])
                ? 'https://player.vimeo.com/video/' . $vimeo[1]
                : $src,
            'mp4' => $src,
            default => $src,
        };
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $type = $HTMLAttributes['data-type'] ?? $HTMLAttributes['type'] ?? 'auto';
        $ratio = $HTMLAttributes['data-ratio'] ?? $HTMLAttributes['ratio'] ?? '16:9';
        $maxWidth = $HTMLAttributes['data-max-width'] ?? $HTMLAttributes['maxWidth'] ?? '100';
        $widthUnit = $HTMLAttributes['data-width-unit'] ?? $HTMLAttributes['widthUnit'] ?? '%';
        $src = $HTMLAttributes['src'] ?? '';

        // Embed URL bepalen
        $embedUrl = $this->getEmbedUrl($src, $type);

        // Ratio naar "w / h"
        [$rw, $rh] = array_pad(array_map('trim', explode(':', (string) $ratio)), 2, null);
        $rw = max(1, (int) ($rw ?: 16));
        $rh = max(1, (int) ($rh ?: 9));
        $ratioCss = $rw . ' / ' . $rh;

        // Wrapper met ALLE styles inline (mag van jou 😎)
        $wrapperAttrs = [
            'class' => trim('responsive '.$type.'-video'),
            "data-{$type}-video" => 'true',
            'data-type' => $type,
            'data-ratio' => $ratio,
            'data-max-width' => $maxWidth,
            'data-width-unit' => $widthUnit,
            'data-src' => $src,
            'style' => sprintf(
                'max-width:%s%s;width:100%%;position:relative;overflow:hidden;aspect-ratio:%s;',
                $maxWidth,
                $widthUnit,
                $ratioCss
            ),
        ];

        // Iframe 100% over de wrapper heen
        $iframeAttrs = [
            'class' => 'external-video',
            'src' => $embedUrl,
            'loading' => 'lazy',
            'allowfullscreen' => 'true',
            'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
            'referrerpolicy' => 'strict-origin-when-cross-origin',
            'style' => 'position:absolute;inset:0;width:100%;height:100%;display:block;border:0;',
        ];

        return [
            'div',
            $wrapperAttrs,
            ['iframe', $iframeAttrs],
        ];
    }
}

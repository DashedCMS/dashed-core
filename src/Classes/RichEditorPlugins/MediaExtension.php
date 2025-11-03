<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class MediaExtension extends Node
{
    public static $name = 'mediaEmbed';
    public static $priority = 100;

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [
                'class' => 'media-embed',
                'style' => 'display:block;max-width:100%;height:auto;',
                'loading' => 'lazy',
                'decoding' => 'async',
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [['tag' => 'div.responsive-image img.media-embed']];
    }

    public function addAttributes(): array
    {
        return [
            'src' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('src'),
                'renderHTML' => fn ($attrs) => ['src' => $attrs->src ?? null],
            ],
            'mediaId' => [
                'parseHTML' => function (\DOMElement $dom) {
                    $parent = $dom->parentNode instanceof \DOMElement ? $dom->parentNode : null;
                    $fromWrapper = $parent?->getAttribute('data-media-id');

                    return $fromWrapper ?: $dom->getAttribute('data-media-id');
                },
                'renderHTML' => fn ($attrs) => ['data-media-id' => $attrs->mediaId ?? null],
            ],
            'alt' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('alt') ?? '',
                'renderHTML' => fn ($attrs) => ['alt' => $attrs->alt ?? ''],
            ],
            'widthUnit' => [
                'parseHTML' => function (\DOMElement $dom) {
                    $parent = $dom->parentNode instanceof \DOMElement ? $dom->parentNode : null;

                    return $parent?->getAttribute('data-width-unit') ?: '%';
                },
                'renderHTML' => fn ($attrs) => ['data-width-unit' => $attrs->widthUnit ?? '%'],
            ],
            'width' => [
                'parseHTML' => function (\DOMElement $dom) {
                    $parent = $dom->parentNode instanceof \DOMElement ? $dom->parentNode : null;
                    $unit = $parent?->getAttribute('data-width-unit') ?: '%';
                    $style = (string) ($dom->getAttribute('style') ?? '');
                    if ($unit === '%') {
                        // width: 60%;
                        if ($style && preg_match('~\bwidth\s*:\s*([0-9.]+)\s*%~i', $style, $m)) {
                            return (string) (float) $m[1];
                        }
                        // fallback wrapper style
                        $wStyle = (string) ($parent?->getAttribute('style') ?? '');
                        if ($wStyle && preg_match('~\bmax-width\s*:\s*([0-9.]+)\s*%~i', $wStyle, $m2)) {
                            return (string) (float) $m2[1];
                        }

                        return '100';
                    }

                    // px
                    if ($style && preg_match('~\bwidth\s*:\s*([0-9.]+)px\b~i', $style, $m)) {
                        return (string) (int) $m[1];
                    }
                    $attr = (string) ($dom->getAttribute('width') ?? '');

                    return $attr !== '' ? (string) (int) $attr : null;
                },
                'renderHTML' => fn ($attrs) => [
                    'width' => ($attrs->widthUnit ?? '%') === 'px' && isset($attrs->width)
                        ? (string) (int) $attrs->width
                        : null,
                ],
            ],
            'height' => [
                'parseHTML' => function (\DOMElement $dom) {
                    $parent = $dom->parentNode instanceof \DOMElement ? $dom->parentNode : null;
                    $unit = $parent?->getAttribute('data-width-unit') ?: '%';
                    if ($unit === '%') {
                        return null; // percentage-modus → height genegeerd
                    }
                    $style = (string) ($dom->getAttribute('style') ?? '');
                    if ($style && preg_match('~\bheight\s*:\s*([0-9.]+)px\b~i', $style, $m)) {
                        return (string) (int) $m[1];
                    }
                    $attr = (string) ($dom->getAttribute('height') ?? '');

                    return $attr !== '' ? (string) (int) $attr : null;
                },
                'renderHTML' => fn ($attrs) => [
                    'height' => ($attrs->widthUnit ?? '%') === 'px' && isset($attrs->height)
                        ? (string) (int) $attrs->height
                        : null,
                ],
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $src = $HTMLAttributes['src'] ?? '';
        $mediaId = $HTMLAttributes['mediaId'] ?? null;
        $alt = $HTMLAttributes['alt'] ?? '';
        $widthUnit = $HTMLAttributes['widthUnit'] ?? '%';
        $width = $HTMLAttributes['width'] ?? ($widthUnit === '%' ? '100' : null);
        $height = $HTMLAttributes['height'] ?? null;

        // wrapper data (ook handig voor edit)
        $wrapperAttrs = [
            'class' => 'responsive-image',
            'data-image' => 'true',
            'data-media-id' => $mediaId ?? '',
            'data-src' => $src ?? '',
            'data-alt' => $alt ?? '',
            'data-width-unit' => $widthUnit,
            'data-width' => $width ?? '',
            'data-height' => $height ?? '',
            'style' => 'display:block;max-width:100%;width:100%;',
        ];

        // img styles
        $imgStyleParts = ['display:block', 'max-width:100%'];
        if ($widthUnit === '%') {
            $imgStyleParts[] = 'width:' . (is_numeric($width) ? (float) $width : 100) . '%';
            $imgStyleParts[] = 'height:auto';
        } else {
            if ($width) {
                $imgStyleParts[] = 'width:'  . (int) $width  . 'px';
            }
            if ($height) {
                $imgStyleParts[] = 'height:' . (int) $height . 'px';
            }
            if (! $height) {
                $imgStyleParts[] = 'height:auto';
            }
        }
        $imgStyle = implode(';', $imgStyleParts) . ';';

        $imgAttrs = HTML::mergeAttributes(
            $this->options['HTMLAttributes'],
            $HTMLAttributes,
            [
                'src' => $src,
                'alt' => $alt,
                'style' => $imgStyle,
                'data-media-id' => $mediaId ?? null,
            ],
        );

        return [
            'div',
            $wrapperAttrs,
            ['img', $imgAttrs],
        ];
    }
}

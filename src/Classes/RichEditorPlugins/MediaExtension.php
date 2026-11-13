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
        // Blijft null: tiptap-php geeft hier de gerenderde attributen, dus
        // `mediaId` bestaat er niet. Breedte, hoogte en stijl hieronder hangen
        // aan dezelfde eigenaardigheid (`widthUnit` is hier altijd `%`) en
        // blijven bewust zoals ze waren: dit is de uitvoer van de publieke site.
        $mediaId = $HTMLAttributes['mediaId'] ?? null;
        $alt = $HTMLAttributes['alt'] ?? '';

        // Het id van de node zelf, alleen voor de wrapper, het adres en de alt-tekst.
        $nodeMediaId = isset($node->attrs) && is_object($node->attrs) ? ($node->attrs->mediaId ?? null) : null;
        $nodeMediaId = is_scalar($nodeMediaId) && $nodeMediaId !== '' && $nodeMediaId !== false ? (string) $nodeMediaId : ($HTMLAttributes['data-media-id'] ?? null);
        $nodeMediaId = is_scalar($nodeMediaId) && (string) $nodeMediaId !== '' ? (string) $nodeMediaId : null;

        if ($nodeMediaId !== null && ($src === '' || $src === null || $alt === '' || $alt === null || $this->pointsToOwnDisk((string) $src))) {
            [$src, $alt] = $this->fromLibrary($nodeMediaId, (string) $src, (string) $alt);
        }
        $widthUnit = $HTMLAttributes['widthUnit'] ?? '%';
        $width = $HTMLAttributes['width'] ?? ($widthUnit === '%' ? '100' : null);
        $height = $HTMLAttributes['height'] ?? null;

        // wrapper data (ook handig voor edit)
        $wrapperAttrs = [
            'class' => 'responsive-image',
            'data-image' => 'true',
            'data-media-id' => $nodeMediaId ?? '',
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

    /**
     * Het adres en de alt-tekst uit de bibliotheek. De alt-tekst alleen als de
     * node er zelf geen heeft. Het adres alleen als de node er geen heeft, of
     * als het opgeslagen adres naar de eigen schijf wijst en dezelfde
     * bestandsnaam draagt als het bestand dat nu bij dit id hoort: inhoud uit
     * een import of een gekopieerde site kan een id dragen dat hier bij een
     * ander bestand hoort, en dan blijft het opgeslagen adres staan.
     *
     * @return array{0: string, 1: string}
     */
    protected function fromLibrary(string $mediaId, string $src, string $alt): array
    {
        if (! function_exists('mediaHelper') || ! ctype_digit($mediaId)) {
            return [$src, $alt];
        }

        $media = rescue(fn () => mediaHelper()->getSingleMedia($mediaId, 'medium'), null, false);

        if (! is_object($media) || blank($media->url ?? null)) {
            return [$src, $alt];
        }

        $url = (string) $media->url;
        $sameFile = false;

        if ($src !== '') {
            $name = $this->fileNameOf($src);
            $sameFile = $name !== '' && in_array($name, [$this->fileNameOf((string) ($media->path ?? '')), $this->fileNameOf($url)], true);

            // Het bestand van dit item is vervangen en de tekst draagt nog het
            // oude adres. Dat oude bestand verdwijnt na de bewaartermijn; als
            // het adres aantoonbaar bij een eerder bestand van HETZELFDE item
            // hoort, telt het als hetzelfde bestand en volgt het huidige adres.
            // Alleen gevraagd als de naam niet al overeenkwam.
            if (! $sameFile && $this->pointsToOwnDisk($src) && $this->isReplacedFileOf($mediaId, $src)) {
                $sameFile = true;
            }
        }

        if ($alt === '' && ($src === '' || $sameFile) && filled($media->altText ?? null)) {
            $alt = (string) $media->altText;
        }

        if ($src === '' || ($sameFile && $this->pointsToOwnDisk($src))) {
            $src = $url;
        }

        return [$src, $alt];
    }

    /**
     * Of het adres naar een bewaard, vervangen bestand van dit item wijst: de
     * map in het pad is de uuid van een mediarij van dit item in de collectie
     * met vervangen bestanden. Eén query per adres, per verzoek onthouden.
     */
    protected function isReplacedFileOf(string $mediaId, string $src): bool
    {
        $item = 'Dashed\\DashedFiles\\Models\\MediaItem';

        if (! class_exists($item) || ! defined($item . '::COLLECTION_REPLACED')
            || ! preg_match('~/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/~i', $src, $match)) {
            return false;
        }

        $key = $mediaId . '|' . strtolower($match[1]);
        $request = app()->bound('request') ? request() : null;
        $memo = $request ? (array) $request->attributes->get('dashed-core.media-embed-replaced', []) : [];

        if (! array_key_exists($key, $memo)) {
            $memo[$key] = (bool) rescue(fn () => \Illuminate\Support\Facades\DB::table('media')
                ->where('uuid', $match[1])
                ->where('model_id', (int) $mediaId)
                ->whereIn('model_type', [$item::MORPH_ALIAS, $item::LEGACY_CLASS])
                ->where('collection_name', $item::COLLECTION_REPLACED)
                ->exists(), false, false);

            $request?->attributes->set('dashed-core.media-embed-replaced', $memo);
        }

        return $memo[$key];
    }

    protected function pointsToOwnDisk(string $src): bool
    {
        if ($src === '') {
            return false;
        }

        $base = rescue(fn () => (string) \Illuminate\Support\Facades\Storage::disk('dashed')->url(''), '', false);

        return strlen($base) >= 2 && str_starts_with($src, $base);
    }

    protected function fileNameOf(string $address): string
    {
        return basename(rawurldecode((string) preg_split('/[?#]/', $address, 2)[0]));
    }
}

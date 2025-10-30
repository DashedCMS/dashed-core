<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class ExternalVideoExtension extends Node
{
    public static $name = 'externalVideo';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [
                'class' => 'external-video',
                'style' => 'aspect-ratio:16/9;width:100%;height:auto;border:0;display:block;',
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [
            ['tag' => 'iframe.external-video'],
        ];
    }

    public function addAttributes(): array
    {
        return [
            'src' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('src'),
                'renderHTML' => fn ($attrs) => ['src' => $attrs->src ?? null],
            ],
            'provider' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('data-provider'),
                'renderHTML' => fn ($attrs) => [
                    'data-provider' => $attrs->provider ?? null,
                ],
            ],
            'ratio' => [
                'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('data-ratio') ?? '16:9',
                'renderHTML' => fn ($attrs) => [
                    'data-ratio' => $attrs->ratio ?? '16:9',
                    'style' => 'aspect-ratio:' . str_replace(':', '/', $attrs->ratio ?? '16:9') . ';width:100%;height:auto;border:0;display:block;',
                ],
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $attrs = HTML::mergeAttributes(
            $this->options['HTMLAttributes'],
            $HTMLAttributes
        );

        return ['iframe', $attrs];
    }
}

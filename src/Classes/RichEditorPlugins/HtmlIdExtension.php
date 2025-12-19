<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Tiptap\Core\Extension;

class HtmlIdExtension extends Extension
{
    public static $name = 'htmlId';

    public function addGlobalAttributes(): array
    {
        return [
            [
                'types' => ['paragraph', 'heading', 'blockquote', 'codeBlock', 'listItem', 'tableCell', 'tableHeader'],
                'attributes' => [
                    'id' => [
                        'default' => null,
                        'parseHTML' => fn (\DOMElement $dom) => $dom->getAttribute('id') ?: null,
                        'renderHTML' => fn ($attrs) => isset($attrs->id) ? ['id' => $attrs->id] : [],
                    ],
                ],
            ],
        ];
    }
}

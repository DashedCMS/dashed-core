<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Tiptap\Core\Extension;
use Tiptap\Marks\Highlight;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\ColorPicker;
use Filament\Support\Facades\FilamentAsset;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;

class MediaLibraryPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        // This method should return an array of PHP TipTap extension objects.
        // See: https://github.com/ueberdosis/tiptap-php

        return [
//            app(Highlight::class, [
//                'options' => ['multicolor' => true],
//            ]),
        ];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        // This method should return an array of URLs to JavaScript files containing
        // TipTap extensions that should be asynchronously loaded into the editor
        // when the plugin is used.

        return [
//            FilamentAsset::getScriptSrc('rich-content-plugins/highlight'),
        ];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        // This method should return an array of `RichEditorTool` objects, which can then be
        // used in the `toolbarButtons()` of the editor.

        // The `jsHandler()` method allows you to access the TipTap editor instance
        // through `$getEditor()`, and `chain()` any TipTap commands to it.
        // See: https://tiptap.dev/docs/editor/api/commands

        // The `action()` method allows you to run an action (registered in the `getEditorActions()`
        // method) when the toolbar button is clicked. This allows you to open a modal to
        // collect additional information from the user before running a command.

        return [
            RichEditorTool::make('highlight')
                ->jsHandler('$getEditor()?.chain().focus().toggleHighlight().run()')
                ->icon(Heroicon::CursorArrowRays),
            RichEditorTool::make('highlightWithCustomColor')
                ->action(arguments: '{ color: $getEditor().getAttributes(\'highlight\')?.[\'data-color\'] }')
                ->icon(Heroicon::CursorArrowRipple),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        // This method should return an array of `Action` objects, which can be used by the tools
        // registered in the `getEditorTools()` method. The name of the action should match
        // the name of the tool that uses it.

        // The `runCommands()` method allows you to run TipTap commands on the editor instance.
        // It accepts an array of `EditorCommand` objects that define the command to run,
        // as well as any arguments to pass to the command. You should also pass in the
        // `editorSelection` argument, which is the current selection in the editor
        // to apply the commands to.

        return [
            Action::make('highlightWithCustomColor')
                ->modalWidth(Width::Large)
                ->fillForm(fn (array $arguments): array => [
                    'color' => $arguments['color'] ?? null,
                ])
                ->schema([
                    ColorPicker::make('color'),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands(
                        [
                            EditorCommand::make(
                                'toggleHighlight',
                                arguments: [[
                                    'color' => $data['color'],
                                ]],
                            ),
                        ],
                        editorSelection: $arguments['editorSelection'],
                    );
                }),
        ];
    }
}

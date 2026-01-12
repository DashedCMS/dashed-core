<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Support\Facades\FilamentAsset;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;

class HtmlIdPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getTipTapPhpExtensions(): array
    {
        return [app(HtmlIdExtension::class)];
    }

    public function getTipTapJsExtensions(): array
    {
        // Let op: dit moet matchen met je asset handle in FilamentAsset::register()
        return [FilamentAsset::getScriptSrc('rich-content-plugins/html-id', 'dashed-core')];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('htmlId')
                ->action(
                    arguments: '{ id: $getEditor()?.getAttributes($getEditor()?.state?.selection?.$from?.parent?.type?.name)?.id }'
                )
                ->label('ID')
                ->icon(Heroicon::FingerPrint),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('htmlId')
                ->modalHeading('ID instellen')
                ->modalWidth(Width::Medium)
                ->schema([
                    TextInput::make('id')
                        ->label('ID (anchor)')
                        ->helperText('Bijv: features, pricing, faq-1')
                        ->regex('/^[A-Za-z][A-Za-z0-9\:\-\_\.]*$/')
                        ->maxLength(80),
                ])
                ->mountUsing(function (Schema $form, array $arguments): void {
                    $form->fill([
                        'id' => $arguments['id'] ?? null,
                    ]);
                })
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands([
                        EditorCommand::make('setHtmlId', arguments: [[
                            'id' => $data['id'] ?? null,
                        ]]),
                    ], editorSelection: $arguments['editorSelection'] ?? null);
                }),
        ];
    }
}

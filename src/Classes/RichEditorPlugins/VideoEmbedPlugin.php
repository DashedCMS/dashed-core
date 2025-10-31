<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Support\Facades\FilamentAsset;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;

class VideoEmbedPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getTipTapPhpExtensions(): array
    {
        return [
            app(ExternalVideoExtension::class)
        ];
    }

    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('rich-content-plugins/external-video', 'dashed-core'),
        ];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('insertExternalVideo')
                ->action()
                ->label('Video')
                ->icon(Heroicon::Play),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('insertExternalVideo')
                ->modalHeading('Video insluiten (werkt nog niet)')
                ->modalWidth(Width::Large)
                ->schema([
                    TextInput::make('url')
                        ->label('Video URL (YouTube, Vimeo of direct .mp4/.webm)')
                        ->required()
                        ->rule('url'),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands(
                        [
                            EditorCommand::make('setExternalVideo', arguments: [[
                                'url' => $data['url'],
                            ]]),
                        ],
                        editorSelection: $arguments['editorSelection'] ?? null,
                    );
                }),
        ];
    }
}

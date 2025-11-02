<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Support\Facades\FilamentAsset;

class VideoEmbedPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getTipTapPhpExtensions(): array
    {
        return [app(ExternalVideoExtension::class)];
    }

    public function getTipTapJsExtensions(): array
    {
        return [FilamentAsset::getScriptSrc('rich-content-plugins/external-video', 'dashed-core')];
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
            // ✅ Insert modal
            Action::make('insertExternalVideo')
                ->modalHeading('Video insluiten')
                ->modalWidth(Width::Large)
                ->schema($this->videoFormSchema())
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands([
                        EditorCommand::make('setExternalVideo', arguments: [[
                            'src' => $data['src'],
                            'type' => $data['type'],
                            'ratio' => $data['ratio'] ?? '16:9',
                            'maxWidth' => $data['maxWidth'] ?? '100',
                            'widthUnit' => $data['widthUnit'] ?? '%',
                        ]]),
                    ], editorSelection: $arguments['editorSelection'] ?? null);
                }),

            // ✅ Edit modal
            Action::make('editExternalVideo')
                ->modalHeading('Video bewerken')
                ->modalWidth(Width::Large)
                ->schema($this->videoFormSchema())
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands([
                        EditorCommand::make('updateExternalVideo', arguments: [[
                            'src' => $data['src'],
                            'type' => $data['type'],
                            'ratio' => $data['ratio'] ?? '16:9',
                            'maxWidth' => $data['maxWidth'] ?? '100',
                            'widthUnit' => $data['widthUnit'] ?? '%',
                        ]]),
                    ], editorSelection: $arguments['editorSelection'] ?? null);
                }),
        ];
    }

    protected function videoFormSchema(): array
    {
        return [
            Select::make('type')
                ->label('Type video')
                ->options([
                    'youtube' => 'YouTube',
                    'vimeo' => 'Vimeo',
                    'mp4' => 'MP4 / WebM',
                    'auto' => 'Automatisch detecteren',
                ])
                ->default('youtube')
                ->reactive(),

            TextInput::make('src')
                ->label('Video URL')
                ->required()
                ->rule('url')
                ->reactive(),

            Select::make('ratio')
                ->label('Beeldverhouding')
                ->options([
                    '16:9' => '16:9 (breed)',
                    '1:1' => '1:1 (vierkant)',
                    '9:16' => '9:16 (story)',
                    '4:3' => '4:3 (klassiek)',
                ])
                ->default('16:9'),

            Group::make()
                ->schema([
                    TextInput::make('maxWidth')
                        ->numeric()
                        ->default(100)
                        ->label('Breedte'),

                    Select::make('widthUnit')
                        ->label('Eenheid')
                        ->options([
                            '%' => '%',
                            'px' => 'px',
                        ])
                        ->default('%'),
                ])
                ->columns(2),

            View::make('dashed-core::richeditor.video-preview')
                ->reactive()
                ->visible(fn($get) => filled($get('src')))
                ->extraAttributes(['class' => 'mt-4']),
        ];
    }
}

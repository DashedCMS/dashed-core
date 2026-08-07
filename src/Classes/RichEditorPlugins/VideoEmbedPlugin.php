<?php

namespace Dashed\DashedCore\Classes\RichEditorPlugins;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
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
                ->label(__('Video'))
                ->icon(Heroicon::Play),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('insertExternalVideo')
                ->modalHeading(__('Video insluiten'))
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

            Action::make('editExternalVideo')
                ->modalHeading(__('Video bewerken'))
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
                ->label(__('Type video'))
                ->options([
                    'youtube' => __('YouTube'),
                    'vimeo' => __('Vimeo'),
                    'mp4' => __('MP4 / WebM'),
                    'auto' => __('Automatisch detecteren'),
                ])
                ->default('youtube')
                ->reactive(),

            TextInput::make('src')
                ->label(__('Video URL'))
                ->required()
                ->rule('url')
                ->reactive(),

            Select::make('ratio')
                ->label(__('Beeldverhouding'))
                ->options([
                    '16:9' => __('16:9 (breed)'),
                    '1:1' => __('1:1 (vierkant)'),
                    '9:16' => __('9:16 (story)'),
                    '4:3' => __('4:3 (klassiek)'),
                ])
                ->default('16:9'),

            Group::make()
                ->schema([
                    TextInput::make('maxWidth')
                        ->numeric()
                        ->default(100)
                        ->label(__('Breedte')),

                    Select::make('widthUnit')
                        ->label(__('Eenheid'))
                        ->options([
                            '%' => __('%'),
                            'px' => __('px'),
                        ])
                        ->default('%'),
                ])
                ->columns(2),
        ];
    }
}

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
use RalphJSmit\Filament\MediaLibrary\Filament\Forms\Components\MediaPicker;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;

class MediaEmbedPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getTipTapPhpExtensions(): array
    {
        return [app(MediaExtension::class)];
    }

    public function getTipTapJsExtensions(): array
    {
        return [FilamentAsset::getScriptSrc('rich-content-plugins/media-embed', 'dashed-core')];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('mediaEmbed')
                ->action()
                ->label('Afbeelding')
                ->icon(Heroicon::Photo),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('mediaEmbed')
                ->modalHeading('Afbeelding toevoegen / bewerken')
                ->modalWidth(Width::Large)
                ->schema($this->imageFormSchema())
                ->mountUsing(function (Action $action, array $arguments) {
                    $defaults = [
                        'mediaId' => null,
                        'src' => '',
                        'widthUnit' => '%',
                        'width' => '100',
                        'height' => null,
                    ];

                    if (isset($arguments['nodeAttrs']) && is_array($arguments['nodeAttrs'])) {
                        $defaults = array_merge(
                            $defaults,
                            array_intersect_key($arguments['nodeAttrs'], $defaults),
                        );
                    }

                    if (($defaults['mediaId'] ?? null)) {
                        $defaults['src'] = $defaults['src'] ?: ($this->resolveMediaUrl((string)$defaults['mediaId']) ?? '');
                    }

                    $action->fillForm($defaults);
                })
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $mediaId = $data['mediaId'] ?? null;
                    $widthUnit = $data['widthUnit'] ?? '%';
                    $width = $data['width'] ?? ($widthUnit === '%' ? '100' : null);
                    $height = $data['height'] ?? null;

                    $src = $data['src'] ?? '';
                    if ($mediaId && empty($src)) {
                        $src = $this->resolveMediaUrl((string)$mediaId) ?? '';
                    }

                    // bij % negeren we height
                    if ($widthUnit === '%') {
                        $height = null;
                        if ($width === null || $width === '') {
                            $width = '100';
                        }
                    }

                    $payload = [
                        'mediaId' => $mediaId,
                        'src' => $src,
                        'widthUnit' => $widthUnit,
                        'width' => $width,
                        'height' => $height,
                    ];

                    // 1) update (als selectie op node staat)
                    $component->runCommands(
                        [EditorCommand::make('updateMediaEmbed', arguments: [[$payload]])],
                        editorSelection: $arguments['editorSelection'] ?? null,
                    );

                    // 2) fallback insert
                    $component->runCommands(
                        [EditorCommand::make('setMediaEmbed', arguments: [[$payload]])],
                        editorSelection: $arguments['editorSelection'] ?? null,
                    );
                }),
        ];
    }

    protected function imageFormSchema(): array
    {
        return [
            Group::make()
                ->schema([
                    mediaHelper()->field('mediaId', 'Media')
                        ->required()
//                        ->afterStateUpdated(function ($state, callable $set) {
//                            if ($state) {
//                                $set('src', $this->resolveMediaUrl((string)$state) ?? '');
//                            }
//                        })
                        ->columnSpanFull(),

//                    TextInput::make('src')
//                        ->label('Afbeelding URL')
//                        ->placeholder('Wordt automatisch gezet vanuit de MediaPicker')
//                        ->helperText('Optioneel: overschrijf de URL handmatig.')
//                        ->rule('url')
//                        ->reactive(),

                    Select::make('widthUnit')
                        ->label('Eenheid')
                        ->options(['%' => '%', 'px' => 'px'])
                        ->default('%')
                        ->reactive(),

                    Group::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('width')
                                ->label('Breedte')
                                ->numeric()
                                ->minValue(1)
                                ->suffix(fn ($get) => $get('widthUnit') === 'px' ? 'px' : '%')
                                ->default(fn ($get) => $get('widthUnit') === '%' ? '100' : null)
                                ->required(fn ($get) => $get('widthUnit') === '%')
                                ->columnSpan(fn ($get) => ($get('widthUnit') === '%') ? 2 : 1)
                                ->helperText(fn ($get) => $get('widthUnit') === '%' ? 'Alleen breedte in % wordt gebruikt.' : null)
                                ->reactive(),

                            TextInput::make('height')
                                ->label('Hoogte')
                                ->numeric()
                                ->minValue(1)
                                ->suffix('px')
                                ->visible(fn ($get) => $get('widthUnit') === 'px')
                                ->reactive(),
                        ]),
                ]),
        ];
    }

    protected function resolveMediaUrl(string $mediaId): ?string
    {
        return mediaHelper()->getSingleMedia($mediaId)->url ?? null;
    }
}

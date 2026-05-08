<?php

namespace Dashed\DashedCore;

use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;

class FilamentRichContentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            Js::make(
                'rich-content-plugins/external-video',
                __DIR__ . '/../resources/js/dist/filament/rich-content-plugins/external-video.js'
            )->loadedOnRequest(),
            Js::make(
                'rich-content-plugins/media-embed',
                __DIR__ . '/../resources/js/dist/filament/rich-content-plugins/media-embed.js'
            )->loadedOnRequest(),
            Js::make(
                'rich-content-plugins/html-id',
                __DIR__ . '/../resources/js/dist/filament/rich-content-plugins/id-attribute.js'
            )->loadedOnRequest(),
            Js::make(
                'rich-editor-fullscreen',
                __DIR__ . '/../resources/js/rich-editor-fullscreen.js'
            ),
            Css::make(
                'rich-editor-fullscreen',
                __DIR__ . '/../resources/css/rich-editor-fullscreen.css'
            ),
        ], 'dashed-core');
    }
}

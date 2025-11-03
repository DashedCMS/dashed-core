<?php

namespace Dashed\DashedCore;

use Filament\Support\Assets\Js;
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
        ], 'dashed-core');
    }
}

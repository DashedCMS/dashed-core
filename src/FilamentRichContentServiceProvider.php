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
            )->loadedOnRequest(), // 👈 only loads when RichEditor is used
        ], 'dashed-core');
    }
}

<?php

namespace Dashed\DashedCore;

use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DashedCorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-core';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                UserResource::class,
                RedirectResource::class,
                NotFoundPageResource::class,
            ])
            ->pages([
                SettingsPage::class,
                GeneralSettingsPage::class,
                SEOSettingsPage::class,
                ImageSettingsPage::class,
                CacheSettingsPage::class,
            ])
            ->widgets([
                //                NotFoundPageStats::class,
                //                NotFoundPageGlobalStats::class,
            ]);
    }

    public function boot(Panel $panel): void {}
}

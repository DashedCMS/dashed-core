<?php

namespace Dashed\DashedCore;

use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Dashed\DashedCore\Filament\Pages\Settings\HorizonPage;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;

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
                GlobalBlockResource::class,
            ])
            ->pages([
                SettingsPage::class,
                GeneralSettingsPage::class,
                SEOSettingsPage::class,
                ImageSettingsPage::class,
                CacheSettingsPage::class,
                HorizonPage::class,
                AccountSettingsPage::class,
            ])
            ->widgets([
                //                NotFoundPageStats::class,
                //                NotFoundPageGlobalStats::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }
}

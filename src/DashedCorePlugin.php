<?php

namespace Dashed\DashedCore;

use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Widgets\NotFoundPageGlobalStats;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Widgets\NotFoundPageStats;
use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
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
            ])
            ->pages([
                SettingsPage::class,
                GeneralSettingsPage::class,
                SEOSettingsPage::class,
                ImageSettingsPage::class,
            ])
            ->widgets([
                NotFoundPageStats::class,
                NotFoundPageGlobalStats::class,
            ]);
    }

    public function boot(Panel $panel): void
    {

    }
}

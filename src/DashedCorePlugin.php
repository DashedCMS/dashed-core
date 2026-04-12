<?php

namespace Dashed\DashedCore;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedCore\Filament\Widgets\WelcomeWidget;
use Dashed\DashedCore\Filament\Resources\RoleResource;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Dashed\DashedCore\Filament\Resources\ExportResource;
use Dashed\DashedCore\Filament\Pages\Settings\HorizonPage;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\AISettingsPage;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Dashed\DashedCore\Filament\Pages\Performance\WebVitalsPage;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\EmailSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Resources\Reviews\ReviewResource;
use Dashed\DashedCore\Filament\Resources\SeoImprovementResource;
use Dashed\DashedCore\Filament\Pages\Settings\ClaudeSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ExportSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ReviewSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SearchSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
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
                RoleResource::class,
                RedirectResource::class,
                NotFoundPageResource::class,
                GlobalBlockResource::class,
                ReviewResource::class,
                SeoImprovementResource::class,
                ExportResource::class,
                EmailTemplateResource::class,
            ])
            ->pages([
                SettingsPage::class,
                GeneralSettingsPage::class,
                SEOSettingsPage::class,
                ImageSettingsPage::class,
                CacheSettingsPage::class,
                HorizonPage::class,
                AccountSettingsPage::class,
                SearchSettingsPage::class,
                AISettingsPage::class,
                ReviewSettingsPage::class,
                ClaudeSettingsPage::class,
                ExportSettingsPage::class,
                EmailSettingsPage::class,
                WebVitalsPage::class,
            ])
            ->widgets([
                WelcomeWidget::class,
                //                NotFoundPageStats::class,
                //                AutomatedTranslationStats::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }
}

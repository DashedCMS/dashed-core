<?php

namespace Dashed\DashedCore;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedCore\Filament\Widgets\WelcomeWidget;
use Dashed\DashedCore\Filament\Resources\RoleResource;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Dashed\DashedCore\Filament\Resources\ExportResource;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Dashed\DashedCore\Filament\Pages\Performance\WebVitalsPage;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\EmailSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Resources\Reviews\ReviewResource;
use Dashed\DashedCore\Filament\Pages\Settings\ExportSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\NotFoundPageSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ReviewSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SearchSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\HorizonDashboardPage;
use Dashed\DashedCore\Filament\Pages\NotificationSubscriptions;
use Dashed\DashedCore\Filament\Pages\Settings\NotificationSettingsPage;
use Dashed\DashedCore\Filament\Pages\Documentation\DocumentationOverviewPage;

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
                ExportResource::class,
                EmailTemplateResource::class,
            ])
            ->pages([
                SettingsPage::class,
                GeneralSettingsPage::class,
                SEOSettingsPage::class,
                ImageSettingsPage::class,
                CacheSettingsPage::class,
                HorizonDashboardPage::class,
                AccountSettingsPage::class,
                SearchSettingsPage::class,
                ReviewSettingsPage::class,
                ExportSettingsPage::class,
                NotFoundPageSettingsPage::class,
                EmailSettingsPage::class,
                NotificationSettingsPage::class,
                NotificationSubscriptions::class,
                WebVitalsPage::class,
                DocumentationOverviewPage::class,
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

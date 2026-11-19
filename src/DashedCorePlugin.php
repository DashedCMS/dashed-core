<?php

namespace Dashed\DashedCore;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedCore\Filament\Widgets\WelcomeWidget;
use Dashed\DashedCore\Filament\Resources\RoleResource;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Dashed\DashedCore\Filament\Resources\ExportResource;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Pages\IntegrationsDashboard;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
use Dashed\DashedCore\Filament\Resources\SentEmailResource;
use Dashed\DashedCore\Filament\Pages\ContentQualityDashboard;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\BusinessProfileSettingsPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Dashed\DashedCore\Filament\Pages\NotificationSubscriptions;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\EmailSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Resources\Reviews\ReviewResource;
use Dashed\DashedCore\Filament\Pages\Settings\ExportSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ReviewSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SearchSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\HorizonDashboardPage;
use Dashed\DashedCore\Filament\Pages\Settings\SecuritySettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\NotFoundPageSettingsPage;
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
                \Dashed\DashedCore\Filament\Resources\ShortLinkResource::class,
                \Dashed\DashedCore\Filament\Resources\AnnouncementBarResource::class,
                NotFoundPageResource::class,
                GlobalBlockResource::class,
                \Dashed\DashedCore\Filament\Resources\PageTemplateResource::class,
                ReviewResource::class,
                ExportResource::class,
                EmailTemplateResource::class,
                SentEmailResource::class,
                \Dashed\DashedCore\Filament\Resources\LoginAttemptResource::class,
                \Dashed\DashedCore\Filament\Resources\ScheduledChangeResource::class,
                \Dashed\DashedCore\Filament\Resources\BulkEditRunResource::class,
                \Dashed\DashedCore\Filament\Resources\AbTestResource::class,
                \Dashed\DashedCore\Filament\Resources\ReportResource::class,
                \Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource::class,
            ])
            ->pages([
                SettingsPage::class,
                GeneralSettingsPage::class,
                SEOSettingsPage::class,
                BusinessProfileSettingsPage::class,
                ImageSettingsPage::class,
                CacheSettingsPage::class,
                HorizonDashboardPage::class,
                IntegrationsDashboard::class,
                \Dashed\DashedCore\Filament\Pages\TrashPage::class,
                \Dashed\DashedCore\Filament\Pages\MyTasksPage::class,
                \Dashed\DashedCore\Filament\Pages\CampaignCalendarPage::class,
                \Dashed\DashedCore\Filament\Pages\ContentCalendarPage::class,
                \Dashed\DashedCore\Filament\Pages\LaunchChecklistPage::class,
                ContentQualityDashboard::class,
                AccountSettingsPage::class,
                SecuritySettingsPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\AbTestSettingsPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\SecurityCheckPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\SystemStatusPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\EmailSuppressionsPage::class,
                SearchSettingsPage::class,
                ReviewSettingsPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\ContentApprovalSettingsPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\AnnouncementBarSettingsPage::class,
                ExportSettingsPage::class,
                NotFoundPageSettingsPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\CleanupSettingsPage::class,
                EmailSettingsPage::class,
                NotificationSettingsPage::class,
                NotificationSubscriptions::class,
                DocumentationOverviewPage::class,
                \Dashed\DashedCore\Filament\Pages\Settings\SatisfactionSettingsPage::class,
            ])
            ->widgets([
                WelcomeWidget::class,
                \Dashed\DashedCore\Filament\Widgets\IntegrationHealthWidget::class,
                \Dashed\DashedCore\Filament\Widgets\SatisfactionChartWidget::class,
                \Dashed\DashedCore\Filament\Widgets\LowSatisfactionWidget::class,
                //                NotFoundPageStats::class,
                //                AutomatedTranslationStats::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }
}

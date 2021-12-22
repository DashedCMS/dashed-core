<?php

namespace Qubiqx\QcommerceCore;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceCore\Classes\PageRouteHandler;
use Qubiqx\QcommerceCore\Commands\CreateAdminUser;
use Qubiqx\QcommerceCore\Commands\CreateSitemap;
use Qubiqx\QcommerceCore\Commands\InstallCommand;
use Qubiqx\QcommerceCore\Commands\InvalidatePasswordResetTokens;
use Qubiqx\QcommerceCore\Commands\UpdateCommand;
use Qubiqx\QcommerceCore\Filament\Pages\FilesPage;
use Qubiqx\QcommerceCore\Filament\Pages\Settings\FormSettingsPage;
use Qubiqx\QcommerceCore\Filament\Pages\Settings\GeneralSettingsPage;
use Qubiqx\QcommerceCore\Filament\Pages\Settings\SettingsPage;
use Qubiqx\QcommerceCore\Filament\Resources\FormResource;
use Qubiqx\QcommerceCore\Filament\Resources\MenuItemResource;
use Qubiqx\QcommerceCore\Filament\Resources\MenuResource;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource;
use Qubiqx\QcommerceCore\Filament\Resources\TranslationResource;
use Qubiqx\QcommerceCore\Models\Page;
use Spatie\LaravelPackageTools\Package;

class QcommerceCoreServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-core';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
        });
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder('routeModels', [
            'page' => [
                'name' => 'Pagina',
                'pluralName' => 'Pagina\'s',
                'class' => Page::class,
                'nameField' => 'name',
                'routeHandler' => PageRouteHandler::class,
            ]
        ]);

        cms()->builder('settingPages', [
            'general' => [
                'name' => 'Algemeen',
                'description' => 'Algemene informatie van de website',
                'icon' => 'cog',
                'page' => GeneralSettingsPage::class,
            ],
            'formNotifications' => [
                'name' => 'Formulier notificaties',
                'description' => 'Beheer meldingen die na het invullen van het formulier worden verstuurd',
                'icon' => 'bell',
                'page' => FormSettingsPage::class,
            ]
        ]);

        $package
            ->name('qcommerce-core')
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filesystems',
                'laravellocalization',
                'media-library',
                'qcommerce-core',
            ])
            ->hasRoutes([
                'frontend',
            ])
            ->hasViews()
            ->hasAssets()
            ->hasCommands([
                CreateAdminUser::class,
                InstallCommand::class,
                UpdateCommand::class,
                InvalidatePasswordResetTokens::class,
                CreateSitemap::class,
            ]);
    }

    protected function getStyles(): array
    {
        return [
            'qcommerce-core' => str_replace('/vendor/qubiqx/qcommerce-core/src', '', str_replace('/packages/qubiqx/qcommerce-core/src', '', __DIR__)) . '/vendor/qubiqx/qcommerce-core/resources/dist/css/qcommerce-core.css',
        ];
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            SettingsPage::class,
            GeneralSettingsPage::class,
            FormSettingsPage::class,
            FilesPage::class,
        ]);
    }

    protected function getResources(): array
    {
        return [
            PageResource::class,
            MenuResource::class,
            MenuItemResource::class,
            FormResource::class,
            TranslationResource::class,
        ];
    }
}

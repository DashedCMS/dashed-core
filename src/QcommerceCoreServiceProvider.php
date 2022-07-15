<?php

namespace Qubiqx\QcommerceCore;

use Livewire\Livewire;
use Flowframe\Drift\Config;
use Flowframe\Drift\DriftManager;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Mail;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceCore\Commands\CreateSitemap;
use Qubiqx\QcommerceCore\Commands\UpdateCommand;
use Qubiqx\QcommerceCore\Commands\InstallCommand;
use Qubiqx\QcommerceCore\Commands\CreateAdminUser;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource;
use Qubiqx\QcommerceCore\Filament\Pages\Settings\SettingsPage;
use Qubiqx\QcommerceCore\Livewire\Frontend\Notification\Toastr;
use Flowframe\Drift\CachingStrategies\FilesystemCachingStrategy;
use Qubiqx\QcommerceCore\Commands\InvalidatePasswordResetTokens;
use Qubiqx\QcommerceCore\Filament\Pages\Settings\GeneralSettingsPage;
use Qubiqx\QcommerceCore\Filament\Pages\Settings\MetadataSettingsPage;

class QcommerceCoreServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-core';

    public function bootingPackage()
    {
        $drift = app(DriftManager::class);

        $drift->registerConfig(new Config(
            name: 'qcommerce', // Will be used in the slug
            filesystemDisk: 'public', // Local, public or s3 for example
            cachingStrategy: FilesystemCachingStrategy::class,
        ));

        Livewire::component('notification.toastr', Toastr::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
        });

        if (! $this->app->environment('production')) {
            Mail::alwaysFrom('support@qubiqx.com');
            Mail::alwaysTo('support@qubiqx.com');
        }
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'general' => [
                    'name' => 'Algemeen',
                    'description' => 'Algemene informatie van de website',
                    'icon' => 'cog',
                    'page' => GeneralSettingsPage::class,
                ],
            ])
        );

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'metaData' => [
                    'name' => 'Meta data',
                    'description' => 'Meta data van de website',
                    'icon' => 'identification',
                    'page' => MetadataSettingsPage::class,
                ],
            ])
        );

        $package
            ->name('qcommerce-core')
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filament-forms-tinyeditor',
                'filesystems',
                'laravellocalization',
                'sentry',
                'activitylog',
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
        return array_merge(parent::getStyles(), [
            'qcommerce-core' => str_replace('/vendor/qubiqx/qcommerce-core/src', '', str_replace('/packages/qubiqx/qcommerce-core/src', '', __DIR__)) . '/vendor/qubiqx/qcommerce-core/resources/dist/css/qcommerce-core.css',
        ]);
    }

    protected function getResources(): array
    {
        return array_merge(parent::getResources(), [
            UserResource::class,
        ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            SettingsPage::class,
            GeneralSettingsPage::class,
            MetadataSettingsPage::class,
        ]);
    }
}

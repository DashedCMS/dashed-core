<?php

namespace Dashed\DashedCore;

use Dashed\DashedCore\Commands\MigrateStorageDataToSpace;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Flowframe\Drift\Config;
use Flowframe\Drift\DriftManager;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedCore\Commands\CreateSitemap;
use Dashed\DashedCore\Commands\UpdateCommand;
use Dashed\DashedCore\Commands\InstallCommand;
use Dashed\DashedCore\Commands\CreateAdminUser;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Dashed\DashedCore\Filament\Resources\UserResource;
use Dashed\DashedCore\Livewire\Frontend\Account\Account;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;
use Dashed\DashedCore\Livewire\Frontend\Notification\Toastr;
use Dashed\DashedCore\Commands\InvalidatePasswordResetTokens;
use Flowframe\Drift\CachingStrategies\FilesystemCachingStrategy;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\MetadataSettingsPage;

class DashedCoreServiceProvider extends PluginServiceProvider
{
    public static string $name = 'dashed-core';

    public function bootingPackage()
    {
        Model::unguard();

        $drift = app(DriftManager::class);

        $drift->registerConfig(new Config(
            name: 'dashed',
            filesystemDisk: config('filesystems.dashed.driver') == 'local' ? 'public' : 'dashed',
            cachingStrategy: \Dashed\DashedCore\Classes\FilesystemCachingStrategy::class,
        ));

        Livewire::component('notification.toastr', Toastr::class);
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.forgot-password', ForgotPassword::class);
        Livewire::component('auth.reset-password', ResetPassword::class);
        Livewire::component('account.account', Account::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
        });

        if (! $this->app->environment('production')) {
            Mail::alwaysFrom('info@dashed.nl');
            Mail::alwaysTo('info@dashed.nl');
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
            ->name('dashed-core')
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filament-forms-tinyeditor',
                'filesystems',
                'file-manager',
                'livewire',
                'laravellocalization',
                'flare',
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

//    protected function getStyles(): array
//    {
//        return array_merge(parent::getStyles(), [
//            'dashed-core' => str_replace('/vendor/dashed/dashed-core/src', '', str_replace('/packages/dashed/dashed-core/src', '', __DIR__)) . '/vendor/dashed/dashed-core/resources/dist/css/filament.css',
//        ]);
//    }

    protected function getResources(): array
    {
        return array_merge(parent::getResources(), [
            UserResource::class,
            RedirectResource::class,
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

<?php

namespace Dashed\DashedCore;

use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Models\Customsetting;
use Livewire\Livewire;
use Dashed\Drift\Config;
use Dashed\Drift\DriftManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedCore\Commands\CreateSitemap;
use Dashed\DashedCore\Commands\UpdateCommand;
use Dashed\DashedCore\Commands\InstallCommand;
use Dashed\DashedCore\Commands\CreateAdminUser;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedCore\Livewire\Frontend\Account\Account;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;
use Dashed\DashedCore\Livewire\Frontend\Notification\Toastr;
use Dashed\DashedCore\Commands\InvalidatePasswordResetTokens;
use Dashed\Drift\CachingStrategies\FilesystemCachingStrategy;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\MetadataSettingsPage;

class DashedCoreServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-core';

    public function packageBooted()
    {
        Model::unguard();

        $drift = app(DriftManager::class);

        $drift->registerConfig(new Config(
            name: 'dashed',
            filesystemDisk: (config('filesystems')['disks']['dashed']['driver'] ?? 'local') == 's3' ? 'dashed' : 'public',
            cachingStrategy: FilesystemCachingStrategy::class,
//            forceLazyLoad: Customsetting::
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

        if (!$this->app->environment('production')) {
            Mail::alwaysFrom('info@dashed.nl');
            Mail::alwaysTo('info@dashed.nl');
        }
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dashed-core');

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

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'metaData' => [
                    'name' => 'Afbeelding',
                    'description' => 'Afbeelding van de website',
                    'icon' => 'photo',
                    'page' => ImageSettingsPage::class,
                ],
            ])
        );

        $package
            ->name(static::$name)
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filament-forms-tinyeditor',
                'filament-tiptap-editor',
                'filesystems',
                'file-manager',
                'livewire',
                'laravellocalization',
                'flare',
                'dashed-core',
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
}

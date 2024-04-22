<?php

namespace Dashed\DashedCore;

use Dashed\DashedCore\Commands\CreateAdminUser;
use Dashed\DashedCore\Commands\CreateSitemap;
use Dashed\DashedCore\Commands\InstallCommand;
use Dashed\DashedCore\Commands\InvalidatePasswordResetTokens;
use Dashed\DashedCore\Commands\UpdateCommand;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageGlobalStats;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageStats;
use Dashed\DashedCore\Livewire\Frontend\Account\Account;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Notification\Toastr;
use Dashed\DashedCore\Livewire\Infolists\SEO\SEOScoreInfoList;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\Drift\CachingStrategies\FilesystemCachingStrategy;
use Dashed\Drift\Config;
use Dashed\Drift\DriftManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            forceLazyLoad: Customsetting::get('image_force_lazy_load', null, false),
        ));

        Livewire::component('notification.toastr', Toastr::class);
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.forgot-password', ForgotPassword::class);
        Livewire::component('auth.reset-password', ResetPassword::class);
        Livewire::component('account.account', Account::class);
        Livewire::component('infolists.seo', SEOScoreInfoList::class);

        //Widgets
        Livewire::component('not-found-page-stats', NotFoundPageStats::class);
        Livewire::component('not-found-page-global-stats', NotFoundPageGlobalStats::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
//            $schedule->command(SeoScan::class)->daily();
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
                'seo' => [
                    'name' => 'SEO',
                    'description' => 'SEO van de website',
                    'icon' => 'identification',
                    'page' => SEOSettingsPage::class,
                ],
            ])
        );

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'image' => [
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
                'seo',
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

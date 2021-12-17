<?php

namespace Qubiqx\QcommerceCore;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceCore\Commands\CreateAdminUser;
use Qubiqx\QcommerceCore\Commands\CreateSitemap;
use Qubiqx\QcommerceCore\Commands\InstallCommand;
use Qubiqx\QcommerceCore\Commands\InvalidatePasswordResetTokens;
use Qubiqx\QcommerceCore\Commands\UpdateCommand;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource;
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

        $package
            ->name('qcommerce-core')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_qcommerce-core_table')
            ->hasCommands([
                CreateAdminUser::class,
                InstallCommand::class,
                UpdateCommand::class,
                InvalidatePasswordResetTokens::class,
                CreateSitemap::class,
            ]);
    }

    protected function getResources(): array
    {
        return [
            PageResource::class
        ];
    }
}

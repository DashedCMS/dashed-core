<?php

namespace Qubiqx\QcommerceCore;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceCore\Commands\CreateAdminUser;
use Qubiqx\QcommerceCore\Commands\CreateSitemap;
use Qubiqx\QcommerceCore\Commands\InstallCommand;
use Qubiqx\QcommerceCore\Commands\InvalidatePasswordResetTokens;
use Qubiqx\QcommerceCore\Commands\UpdateCommand;
use Qubiqx\QcommerceCore\Filament\Resources\MenuItemResource;
use Qubiqx\QcommerceCore\Filament\Resources\MenuResource;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource;
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

        cms()->routeModels('page', [
            'name' => 'Pagina',
            'pluralName' => 'Pagina\'s',
            'class' => Page::class,
            'nameField' => 'name'
        ]);

        $package
            ->name('qcommerce-core')
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filesystems',
                'laravellocalization',
                'media-library',
                'qcommerce-core'
            ])
            ->hasRoutes([
                'frontend'
            ])
            ->hasViews()
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
            PageResource::class,
            MenuResource::class,
            MenuItemResource::class,
        ];
    }
}

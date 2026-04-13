<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Laravel\Horizon\MasterSupervisor;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonQueueStats;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonOverviewStats;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonWaitTimeChart;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonFailedJobsTable;
use Dashed\DashedCore\Filament\Widgets\Horizon\HorizonThroughputChart;

class HorizonDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Horizon';

    protected static string|UnitEnum|null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 100000;

    protected static ?string $title = 'Horizon Dashboard';

    protected string $view = 'dashed-core::horizon.dashboard';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view_horizon');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('queueRestart')
                ->label('Queue herstarten')
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    Artisan::call('queue:restart');

                    Notification::make()
                        ->title('Queue restart signaal verstuurd')
                        ->body('Workers stoppen na hun huidige job.')
                        ->success()
                        ->send();
                }),

            Action::make('horizonTerminate')
                ->label('Horizon stoppen')
                ->color('danger')
                ->icon('heroicon-o-stop')
                ->requiresConfirmation()
                ->modalHeading('Horizon stoppen')
                ->modalDescription('Weet je het zeker? Horizon stopt volledig en moet handmatig herstart worden.')
                ->action(function () {
                    $this->sendSignalToMasters(SIGTERM);

                    Notification::make()
                        ->title('Horizon wordt gestopt')
                        ->warning()
                        ->send();
                }),

            Action::make('pauseAll')
                ->label('Alles pauzeren')
                ->color('warning')
                ->icon('heroicon-o-pause')
                ->action(function () {
                    $this->sendSignalToMasters(SIGUSR2);

                    Notification::make()
                        ->title('Alle supervisors gepauzeerd')
                        ->warning()
                        ->send();
                }),

            Action::make('resumeAll')
                ->label('Alles hervatten')
                ->color('success')
                ->icon('heroicon-o-play')
                ->action(function () {
                    $this->sendSignalToMasters(SIGCONT);

                    Notification::make()
                        ->title('Alle supervisors hervat')
                        ->success()
                        ->send();
                }),

            ActionGroup::make($this->getQueuePauseActions())
                ->label('Queue beheer')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray'),
        ];
    }

    protected function sendSignalToMasters(int $signal): void
    {
        $masters = collect(app(MasterSupervisorRepository::class)->all())
            ->filter(fn ($master) => Str::startsWith($master->name, MasterSupervisor::basename()))
            ->all();

        collect(Arr::pluck($masters, 'pid'))
            ->each(fn ($pid) => posix_kill($pid, $signal));
    }

    protected function sendSignalToSupervisor(string $name, int $signal): void
    {
        $supervisors = app(SupervisorRepository::class)->all();

        $supervisor = collect($supervisors)->first(function ($supervisor) use ($name) {
            return Str::startsWith($supervisor->name, MasterSupervisor::basename())
                && Str::endsWith($supervisor->name, $name);
        });

        if ($supervisor && $supervisor->pid) {
            posix_kill($supervisor->pid, $signal);
        }
    }

    protected function getQueuePauseActions(): array
    {
        $supervisors = app(SupervisorRepository::class)->all();

        if (empty($supervisors)) {
            return [
                Action::make('noSupervisors')
                    ->label('Geen actieve supervisors')
                    ->disabled(),
            ];
        }

        $actions = [];

        foreach ($supervisors as $supervisor) {
            $name = $supervisor->name;
            $isPaused = ($supervisor->status ?? '') === 'paused';

            if ($isPaused) {
                $actions[] = Action::make('resume_'.str_replace([' ', ':'], '_', $name))
                    ->label("Hervatten: {$name}")
                    ->icon('heroicon-o-play')
                    ->action(function () use ($name) {
                        $this->sendSignalToSupervisor($name, SIGCONT);

                        Notification::make()
                            ->title("Supervisor {$name} hervat")
                            ->success()
                            ->send();
                    });
            } else {
                $actions[] = Action::make('pause_'.str_replace([' ', ':'], '_', $name))
                    ->label("Pauzeren: {$name}")
                    ->icon('heroicon-o-pause')
                    ->action(function () use ($name) {
                        $this->sendSignalToSupervisor($name, SIGUSR2);

                        Notification::make()
                            ->title("Supervisor {$name} gepauzeerd")
                            ->warning()
                            ->send();
                    });
            }
        }

        return $actions;
    }

    public function getHeaderWidgets(): array
    {
        return [
            HorizonOverviewStats::class,
            HorizonQueueStats::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            HorizonThroughputChart::class,
            HorizonWaitTimeChart::class,
            HorizonFailedJobsTable::class,
        ];
    }
}

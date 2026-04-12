<?php

namespace Dashed\DashedCore\Filament\Widgets\Horizon;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Laravel\Horizon\Contracts\JobRepository;

class HorizonFailedJobsTable extends Widget
{
    protected static ?int $sort = 5;

    public ?string $poll = '5s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'dashed-core::widgets.horizon.failed-jobs-table';

    public function getHeading(): string
    {
        return 'Failed Jobs';
    }

    protected function getViewData(): array
    {
        $jobs = rescue(
            fn () => collect(app(JobRepository::class)->getFailed()),
            collect(),
            false
        );

        return [
            'failedJobs' => $jobs,
        ];
    }

    public function retryJob(string $id): void
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        Notification::make()
            ->title('Job wordt opnieuw geprobeerd')
            ->success()
            ->send();
    }

    public function deleteJob(string $id): void
    {
        rescue(
            fn () => app(JobRepository::class)->deleteFailed($id),
            report: false
        );

        Notification::make()
            ->title('Failed job verwijderd')
            ->success()
            ->send();
    }

    public function retryAllJobs(): void
    {
        $jobs = rescue(
            fn () => collect(app(JobRepository::class)->getFailed()),
            collect(),
            false
        );

        foreach ($jobs as $job) {
            Artisan::call('horizon:retry', ['id' => $job->id]);
        }

        Notification::make()
            ->title('Alle failed jobs worden opnieuw geprobeerd')
            ->success()
            ->send();
    }

    public function flushFailed(): void
    {
        Artisan::call('queue:flush');

        Notification::make()
            ->title('Alle failed jobs verwijderd')
            ->success()
            ->send();
    }
}

<?php

namespace Dashed\DashedCore\Filament\Widgets\Horizon;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;

class HorizonOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 100;

    public ?string $poll = '5s';

    protected function getStats(): array
    {
        $jobs = app(JobRepository::class);
        $metrics = app(MetricsRepository::class);

        $pending = $jobs->countPending();
        $failed = $jobs->countFailed();
        $throughput = round($metrics->jobsProcessedPerMinute(), 1);
        $longestQueue = $metrics->queueWithMaximumRuntime();
        $maxRuntimeMs = $longestQueue ? $metrics->runtimeForQueue($longestQueue) : 0;
        $maxRuntime = round($maxRuntimeMs / 1000, 2);

        $failedStat = Stat::make('Failed jobs', $failed)
            ->description('Mislukte jobs');

        if ($failed > 0) {
            $failedStat = $failedStat->color('danger');
        }

        return [
            Stat::make('Jobs in Queue', $pending)
                ->description('Wachtende jobs (alle queues)'),
            Stat::make('Jobs/minuut', $throughput)
                ->description('Verwerkte jobs per minuut'),
            Stat::make('Langste runtime', $maxRuntime.'s')
                ->description($longestQueue ? "Queue: {$longestQueue}" : 'Geen actieve queues'),
            $failedStat,
        ];
    }
}

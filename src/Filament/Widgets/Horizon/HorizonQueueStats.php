<?php

namespace Dashed\DashedCore\Filament\Widgets\Horizon;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;

class HorizonQueueStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public ?string $poll = '5s';

    protected function getHeading(): ?string
    {
        return 'Queues';
    }

    protected function getStats(): array
    {
        $workload = app(WorkloadRepository::class);
        $metrics = app(MetricsRepository::class);

        $queues = collect($workload->get());

        if ($queues->isEmpty()) {
            return [
                Stat::make('Queues', 'Geen actieve queues')
                    ->color('gray'),
            ];
        }

        return $queues->map(function ($queue) use ($metrics): Stat {
            $queue = (object) $queue;
            $name = $queue->name;
            $pending = $queue->length ?? 0;
            $runtimeMs = rescue(fn () => $metrics->runtimeForQueue($name), 0, false);

            if ($pending > 100) {
                $color = 'danger';
            } elseif ($pending > 10) {
                $color = 'warning';
            } else {
                $color = 'success';
            }

            $runtimeSec = round($runtimeMs / 1000, 2);

            return Stat::make($name, $pending.' pending')
                ->description('Runtime: '.$runtimeSec.'s')
                ->color($color);
        })->values()->all();
    }
}

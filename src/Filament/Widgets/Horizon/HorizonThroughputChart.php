<?php

namespace Dashed\DashedCore\Filament\Widgets\Horizon;

use Filament\Widgets\ChartWidget;
use Laravel\Horizon\Contracts\MetricsRepository;

class HorizonThroughputChart extends ChartWidget
{
    protected static ?int $sort = 3;

    public ?string $poll = '5s';

    protected ?string $heading = 'Throughput over tijd';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $metrics = app(MetricsRepository::class);
        $queues = $metrics->measuredQueues();

        $colors = [
            'rgba(0, 210, 205, 1)',
            'rgba(216, 255, 51, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(153, 102, 255, 1)',
        ];

        $datasets = [];
        $labels = [];

        foreach ($queues as $index => $queue) {
            $snapshots = $metrics->snapshotsForQueue($queue);
            $color = $colors[$index % count($colors)];

            $data = [];
            $queueLabels = [];

            foreach ($snapshots as $snapshot) {
                $data[] = round($snapshot->throughput, 1);
                $queueLabels[] = date('H:i', $snapshot->time);
            }

            if (empty($labels) && ! empty($queueLabels)) {
                $labels = $queueLabels;
            }

            $datasets[] = [
                'label' => $queue,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $color,
                'fill' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

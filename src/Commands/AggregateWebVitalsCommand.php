<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\WebVital;
use Dashed\DashedCore\Models\WebVitalDaily;

class AggregateWebVitalsCommand extends Command
{
    protected $signature = 'dashed:aggregate-vitals {--date= : YYYY-MM-DD to aggregate, defaults to yesterday}';

    protected $description = 'Aggregate raw web_vitals rows into the daily p75 rollup table';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();

        $groups = WebVital::query()
            ->selectRaw('site_id, metric, device, url, COUNT(*) as sample_count')
            ->whereDate('created_at', $date)
            ->groupBy('site_id', 'metric', 'device', 'url')
            ->get();

        $this->info("Aggregating {$groups->count()} groups for {$date}");

        foreach ($groups as $group) {
            $values = WebVital::query()
                ->where('site_id', $group->site_id)
                ->where('metric', $group->metric)
                ->where('device', $group->device)
                ->where('url', $group->url)
                ->whereDate('created_at', $date)
                ->orderBy('value')
                ->pluck('value')
                ->all();

            $p75 = $this->percentile($values, 0.75);

            WebVitalDaily::updateOrCreate(
                [
                    'site_id' => $group->site_id,
                    'date' => $date,
                    'metric' => $group->metric,
                    'url_pattern' => $group->url,
                    'device' => $group->device,
                ],
                [
                    'p75' => $p75,
                    'sample_count' => $group->sample_count,
                ],
            );
        }

        return self::SUCCESS;
    }

    protected function percentile(array $values, float $p): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $count = count($values);
        $rank = $p * ($count - 1);
        $lo = (int) floor($rank);
        $hi = (int) ceil($rank);

        if ($lo === $hi) {
            return (float) $values[$lo];
        }

        $fraction = $rank - $lo;

        return (float) ($values[$lo] + ($values[$hi] - $values[$lo]) * $fraction);
    }
}

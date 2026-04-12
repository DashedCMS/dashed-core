<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\WebVital;

class PruneWebVitalsCommand extends Command
{
    protected $signature = 'dashed:prune-vitals {--days=30 : Delete raw rows older than this many days}';

    protected $description = 'Delete raw web_vitals rows older than N days (rollups are kept forever)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = WebVital::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} raw rows older than {$days} days");

        return self::SUCCESS;
    }
}

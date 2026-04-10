<?php

namespace Dashed\DashedCore\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Dashed\DashedCore\Models\Export;
use Dashed\DashedCore\Models\Customsetting;

class CleanupOldExports extends Command
{
    protected $signature = 'dashed:cleanup-old-exports';

    protected $description = 'Delete exports older than the configured retention period';

    public function handle(): int
    {
        $days = (int) Customsetting::get('exports_retention_days', null, 365);

        if ($days < 1) {
            $days = 365;
        }

        $cutoff = Carbon::now()->subDays($days);

        $count = 0;
        Export::where('created_at', '<', $cutoff)
            ->chunkById(100, function ($exports) use (&$count) {
                foreach ($exports as $export) {
                    $export->delete();
                    $count++;
                }
            });

        $this->info("Deleted {$count} exports older than {$days} days.");

        return self::SUCCESS;
    }
}

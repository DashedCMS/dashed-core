<?php

namespace Dashed\DashedCore\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\NotFoundPage;
use Dashed\DashedCore\Models\NotFoundPageOccurrence;

class CleanupOldNotFoundPageOccurrences extends Command
{
    protected $signature = 'dashed:cleanup-old-not-found-page-occurrences';

    protected $description = 'Verwijder 404-occurrences ouder dan de geconfigureerde bewaartermijn (default 30 dagen)';

    public function handle(): int
    {
        $days = (int) Customsetting::get('not_found_page_occurrences_retention_days', null, 30);

        if ($days < 1) {
            $days = 30;
        }

        $cutoff = Carbon::now()->subDays($days);

        $count = 0;
        NotFoundPageOccurrence::where('created_at', '<', $cutoff)
            ->chunkById(500, function ($occurrences) use (&$count) {
                $touchedPageIds = [];
                foreach ($occurrences as $occurrence) {
                    $touchedPageIds[$occurrence->not_found_page_id] = true;
                    $occurrence->forceDelete();
                    $count++;
                }

                NotFoundPage::whereIn('id', array_keys($touchedPageIds))
                    ->withTrashed()
                    ->each(function (NotFoundPage $page) {
                        $total = $page->occurrences()->count();
                        $latest = $page->occurrences()->latest('created_at')->value('created_at');
                        $page->forceFill([
                            'total_occurrences' => $total,
                            'last_occurrence' => $latest,
                        ])->save();
                    });
            });

        $this->info("Deleted {$count} not-found-page-occurrences older than {$days} days.");

        return self::SUCCESS;
    }
}

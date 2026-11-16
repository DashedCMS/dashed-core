<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Search\SearchIndexer;
use Dashed\DashedCore\Models\BackgroundTask;
use Dashed\DashedCore\Models\Concerns\HasSearchIndex;

class RebuildSearchIndexCommand extends Command
{
    protected $signature = 'dashed:rebuild-search-index';

    protected $description = 'Herbouwt de zoekindex (dashed__search_index) voor alle doorzoekbare modellen.';

    public function handle(SearchIndexer $indexer): int
    {
        $models = collect(cms()->builder('routeModels'))
            ->pluck('class')
            ->unique()
            ->filter(fn ($class) => in_array(HasSearchIndex::class, class_uses_recursive($class), true));

        if ($models->isEmpty()) {
            $this->warn('Geen modellen met HasSearchIndex gevonden.');

            return self::SUCCESS;
        }

        // In het takenicoon van de superadmins, zonder melding in de bel: dit
        // draait vanaf de opdrachtregel en heeft geen starter.
        // Het totaal vooraf, voor de balk en de resterende tijd: één telling per model.
        $grandTotal = (int) rescue(fn () => collect($models)->sum(fn (string $class) => $class::query()->count()), 0, report: false);
        $task = BackgroundTask::begin('search-index', __('Zoekindex opnieuw opbouwen'), $grandTotal ?: null, notify: false);
        $total = 0;

        try {
            foreach ($models as $class) {
                $count = 0;

                $class::query()->chunkById(200, function ($records) use ($indexer, &$count, &$total, $task) {
                    foreach ($records as $record) {
                        $indexer->index($record);
                        $count++;
                        $total++;
                    }

                    $task->progress($total);
                });

                $this->info($class.': '.$count.' geindexeerd.');
            }
        } catch (\Throwable $e) {
            $task->fail($e);

            throw $e;
        }

        $task->finish(__(':aantal items geïndexeerd.', ['aantal' => $total]));

        return self::SUCCESS;
    }
}

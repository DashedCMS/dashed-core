<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Search\SearchIndexer;
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

        foreach ($models as $class) {
            $count = 0;

            $class::query()->chunkById(200, function ($records) use ($indexer, &$count) {
                foreach ($records as $record) {
                    $indexer->index($record);
                    $count++;
                }
            });

            $this->info($class.': '.$count.' geindexeerd.');
        }

        return self::SUCCESS;
    }
}

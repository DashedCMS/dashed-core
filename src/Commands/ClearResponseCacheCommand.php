<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;
use Dashed\DashedCore\Classes\Caching\ResponseCacheStore;

class ClearResponseCacheCommand extends Command
{
    protected $signature = 'dashed:clear-response-cache';

    protected $description = 'Leegt de opgeslagen pagina\'s van de responsecache (alle sites).';

    public function handle(): int
    {
        CacheInvalidator::flushAll();

        $this->info('Responsecache geleegd (' . (ResponseCacheStore::usesTags() ? 'standaardstore met tags' : 'eigen map: ' . ResponseCacheStore::path()) . ').');

        return self::SUCCESS;
    }
}

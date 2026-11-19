<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars;

class RefreshAnnouncementBarCacheCommand extends Command
{
    protected $signature = 'dashed:announcement-bars:refresh-cache';

    protected $description = 'Leegt de responscache van sites waar een actiebalk net begon of eindigde';

    public function handle(): int
    {
        $sites = AnnouncementBars::flushPassedBoundaries();

        if ($sites !== []) {
            $this->info('Responscache geleegd voor: ' . implode(', ', $sites));
        }

        return self::SUCCESS;
    }
}

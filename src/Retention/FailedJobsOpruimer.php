<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Laravel ruimt mislukte taken zelf op met queue:prune-failed. Dat werkt goed;
 * wat ontbrak is dat de termijn zichtbaar en instelbaar was naast de rest.
 */
class FailedJobsOpruimer implements Opruimer
{
    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $voor = DB::table('failed_jobs')->count();

        if ($droog) {
            return DB::table('failed_jobs')
                ->where($termijn->datumkolom(), '<', now()->subDays($termijn->dagen()))
                ->count();
        }

        Artisan::call('queue:prune-failed', ['--hours' => $termijn->dagen() * 24]);

        return max(0, $voor - DB::table('failed_jobs')->count());
    }
}

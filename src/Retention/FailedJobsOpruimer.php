<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        // PruneRunner slaat een ontbrekende tabel al over voordat hij hier
        // komt, maar deze klasse is ook los aan te roepen. Dan hoort een
        // installatie zonder queue-tabellen 0 terug te krijgen, niet een
        // "no such table"-crash.
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        // Hetzelfde getal in beide standen, want anders zegt --dry-run niets:
        // het verschil tussen voor en na telt ook mislukte taken mee die tijdens
        // het opruimen bijkwamen of die een ander proces wegnam, en dat zijn
        // afwijkingen die niets met de bewaartermijn te maken hebben. Deze
        // telling beantwoordt in beide standen dezelfde vraag: hoeveel taken
        // vallen buiten de termijn.
        $aantal = DB::table('failed_jobs')
            ->where($termijn->datumkolom(), '<', now()->subDays($termijn->dagen()))
            ->count();

        if ($droog) {
            return $aantal;
        }

        // Het verwijderen blijft bij Laravel zelf: queue:prune-failed werkt in
        // porties en volgt de ingestelde failer.
        Artisan::call('queue:prune-failed', ['--hours' => $termijn->dagen() * 24]);

        return $aantal;
    }
}

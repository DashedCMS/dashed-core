<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Retention\PruneRunner;

/**
 * De eerste opruimronde na het invoeren van de bewaartermijnen. Draait in de
 * wachtrij en niet in de migratie: op een installatie met miljoenen rijen zou
 * synchroon opruimen de deploy tientallen minuten tot uren ophouden, en een
 * afgebroken deploy zou een half gedraaide migratie achterlaten.
 */
class PruneAllRetentionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function handle(PruneRunner $runner): void
    {
        foreach ($runner->draai(null, 1000, false) as $regel) {
            if ($regel['fout'] !== null) {
                Log::warning('Eerste opruimronde: ' . $regel['label'] . ' faalde: ' . $regel['fout']);

                continue;
            }

            Log::info('Eerste opruimronde: ' . $regel['label'] . ', ' . $regel['aantal'] . ' rijen verwijderd.');
        }
    }
}

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
 * Een opruimronde die een migratie klaarzet: de eerste na het invoeren van de
 * bewaartermijnen, en later die voor `notifications` alleen. Draait in de
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

    /**
     * Sleutel van één register-entry; null is het hele register. Met een
     * standaardwaarde op de eigenschap zelf, zodat een job die al in de
     * wachtrij stond van voor deze eigenschap gewoon het hele register doet.
     */
    public ?string $alleen = null;

    public function __construct(?string $alleen = null)
    {
        $this->alleen = $alleen;
    }

    public function handle(PruneRunner $runner): void
    {
        foreach ($runner->draai($this->alleen, 1000, false) as $regel) {
            if ($regel['fout'] !== null) {
                Log::warning('Opruimronde: ' . $regel['label'] . ' faalde: ' . $regel['fout']);

                continue;
            }

            // Een overgeslagen entry zonder fout heeft geen rijen geteld omdat
            // de tabel hier niet bestaat, niet omdat er niets op te ruimen
            // viel. Zonder dit onderscheid leest "0 rijen verwijderd" op een
            // installatie zonder dat pakket als een geslaagde, lege opruiming
            // in plaats van als "dit logboek bestaat hier niet".
            if ($regel['overgeslagen']) {
                Log::info('Opruimronde: ' . $regel['label'] . ' overgeslagen: ' . $regel['reden']);

                continue;
            }

            Log::info('Opruimronde: ' . $regel['label'] . ', ' . $regel['aantal'] . ' rijen verwijderd.');
        }
    }
}

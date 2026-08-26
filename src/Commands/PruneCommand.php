<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Retention\PruneRunner;

/**
 * Ruimt alles op wat in het bewaartermijnenregister staat. Vervangt de losse
 * opruim-commands die elk hun eigen termijn bepaalden.
 */
class PruneCommand extends Command
{
    protected $signature = 'dashed:prune
        {--only= : Alleen deze ene sleutel opruimen}
        {--dry-run : Alleen tellen, niets verwijderen}
        {--chunk=1000 : Hoeveel rijen per portie}';

    protected $description = 'Ruim logboeken en gebeurtenissentabellen op volgens hun bewaartermijn.';

    public function handle(PruneRunner $runner): int
    {
        $droog = (bool) $this->option('dry-run');
        $uitkomst = $runner->draai(
            $this->option('only') ?: null,
            max(1, (int) $this->option('chunk')),
            $droog,
        );

        if ($uitkomst === []) {
            $this->warn(__('Er staat niets in het bewaartermijnenregister.'));

            return self::SUCCESS;
        }

        $fouten = 0;

        foreach ($uitkomst as $regel) {
            if ($regel['overgeslagen']) {
                $fouten++;
                $this->warn($regel['label'] . ': overgeslagen, ' . $regel['fout']);

                continue;
            }

            if ($regel['fout'] !== null) {
                $fouten++;
                $this->error($regel['label'] . ': ' . $regel['fout']);

                continue;
            }

            $this->line($regel['label'] . ': ' . $regel['aantal'] . ($droog ? ' zouden verdwijnen' : ' verwijderd'));
        }

        $this->info(collect($uitkomst)->sum('aantal') . ($droog ? ' rijen zouden verdwijnen.' : ' rijen verwijderd.'));

        return $fouten > 0 ? self::FAILURE : self::SUCCESS;
    }
}

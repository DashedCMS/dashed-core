<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Retention\PruneRunner;
use Dashed\DashedCore\Retention\RetentionRegistry;

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

    public function handle(PruneRunner $runner, RetentionRegistry $register): int
    {
        $droog = (bool) $this->option('dry-run');
        $alleen = $this->option('only') ?: null;

        // Een sleutel die niet bestaat is een typfout van de aanroeper, geen
        // leeg register. Zonder dit onderscheid meldt een verkeerd gespelde
        // --only dat er niets op te ruimen valt en eindigt hij op nul, dus
        // denkt een cron of een beheerder dat het gelukt is.
        if ($alleen !== null && $register->vind($alleen) === null) {
            $this->error(__('Onbekende sleutel: :sleutel', ['sleutel' => $alleen]));
            $this->line(__('Bekende sleutels: :sleutels', [
                'sleutels' => implode(', ', collect($register->alles())->map->sleutel()->all()),
            ]));

            return self::FAILURE;
        }

        $uitkomst = $runner->draai($alleen, max(1, (int) $this->option('chunk')), $droog);

        if ($uitkomst === []) {
            $this->warn(__('Er staat niets in het bewaartermijnenregister.'));

            return self::SUCCESS;
        }

        $fouten = 0;

        foreach ($uitkomst as $regel) {
            if ($regel['overgeslagen']) {
                // Een overslag om een echte fout (de vooraf-haak liep stuk) telt
                // mee als mislukking: daar is iets kapot. Een overslag om een
                // ontbrekende tabel (`reden` gevuld, `fout` leeg) is dat niet: op
                // deze installatie bestaat dat logboek gewoon niet, en dat mag de
                // dagelijkse cron niet laten falen.
                if ($regel['fout'] !== null) {
                    $fouten++;
                    $this->warn($regel['label'] . ': overgeslagen, ' . $regel['fout']);
                } else {
                    $this->line($regel['label'] . ': overgeslagen, ' . ($regel['reden'] ?? ''));
                }

                continue;
            }

            if ($regel['fout'] !== null) {
                $fouten++;
                $this->error($regel['label'] . ': ' . $regel['fout']);

                continue;
            }

            $this->line($regel['label'] . ': ' . $regel['aantal'] . ($droog ? ' zouden verdwijnen' : ' verwijderd'));

            if ($regel['haak_overgeslagen']) {
                $this->line($regel['label'] . ': ' . __('voorbereiding overgeslagen, een droge run schrijft niets.'));
            }
        }

        $this->info(collect($uitkomst)->sum('aantal') . ($droog ? ' rijen zouden verdwijnen.' : ' rijen verwijderd.'));

        return $fouten > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Throwable;
use Carbon\Carbon;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Loopt het register af. Een entry die klapt sleept de rest niet mee: op een
 * productieserver waar één tabel ontbreekt omdat een pakket er niet staat,
 * moet de rest gewoon opgeruimd worden.
 */
class PruneRunner
{
    public function __construct(protected readonly RetentionRegistry $registry)
    {
    }

    /**
     * @return array<int, array{sleutel: string, label: string, aantal: int, fout: ?string, overgeslagen: bool}>
     */
    public function draai(?string $alleen, int $portie, bool $droog): array
    {
        $uitkomst = [];

        foreach ($this->registry->alles() as $retention) {
            if ($alleen !== null && $retention->sleutel() !== $alleen) {
                continue;
            }

            $uitkomst[] = $this->draaiEen($retention, $portie, $droog);
        }

        return $uitkomst;
    }

    /**
     * @return array{sleutel: string, label: string, aantal: int, fout: ?string, overgeslagen: bool}
     */
    protected function draaiEen(Retention $retention, int $portie, bool $droog): array
    {
        $regel = [
            'sleutel' => $retention->sleutel(),
            'label' => $retention->labelTekst(),
            'aantal' => 0,
            'fout' => null,
            'overgeslagen' => false,
        ];

        // De haak draait voor de eerste verwijdering. Faalt hij, dan blijft
        // alles staan: half opruimen is erger dan niet opruimen, want de
        // cijfers die de haak moest veiligstellen zijn dan al weg.
        if ($haak = $retention->voorafHaak()) {
            try {
                $haak($this->dekkingsGrens($retention));
            } catch (Throwable $e) {
                report($e);

                return [...$regel, 'fout' => $e->getMessage(), 'overgeslagen' => true];
            }
        }

        try {
            $opruimer = $this->opruimerVoor($retention);

            foreach ($retention->termijnen() as $termijn) {
                $regel['aantal'] += $opruimer->ruimOp($termijn, $portie, $droog);
            }
        } catch (Throwable $e) {
            report($e);
            $regel['fout'] = $e->getMessage();
        }

        return $regel;
    }

    protected function opruimerVoor(Retention $retention): Opruimer
    {
        if ($eigen = $retention->eigenOpruimer()) {
            return $eigen;
        }

        $tabel = $retention->tabelNaam();

        if ($tabel === null) {
            throw new \RuntimeException(
                'Retention "' . $retention->sleutel() . '" heeft geen tabel en geen eigen opruimer.'
            );
        }

        return new TabelOpruimer($tabel, $retention->tabelKolom());
    }

    /**
     * De grens waar alles wat verdwijnt vóór ligt: de kortste termijn, niet de
     * langste. Een tabel met meerdere termijnen (`notifications` bijvoorbeeld,
     * veertien dagen na lezen en zestig dagen na aanmaken) verliest al rijen
     * zodra de eerste, kortste termijn ze raakt. Neem je de langste termijn,
     * dan ligt die grens verder terug dan wat de kortste termijn al opruimt,
     * en heeft de haak niet alles veiliggesteld wat zojuist verdween.
     */
    protected function dekkingsGrens(Retention $retention): Carbon
    {
        $dagen = collect($retention->termijnen())->map(fn (Termijn $t) => $t->dagen())->min() ?? 0;

        return Carbon::now()->subDays((int) $dagen);
    }
}

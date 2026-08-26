<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Ruimt op over de primaire sleutel in vensters, niet over de datum.
 *
 * `DELETE ... WHERE created_at < ? LIMIT 1000` heeft een index op die
 * datumkolom nodig. `dashed__popup_views` heeft alleen (popup_id, created_at),
 * en met een samengestelde index waarvan de eerste kolom ontbreekt kan MySQL
 * daar niets mee. Op zes miljoen rijen scant hij dan de hele tabel, duizend
 * rijen per keer, zesduizend keer achter elkaar.
 *
 * Een bereik op de primaire sleutel is altijd indexdekkend. Het venster
 * schuift op naar de eerstvolgende bestaande sleutel, zodat een tabel met
 * gaten (na een eerdere opruiming bijvoorbeeld) niet duizenden lege rondes
 * kost.
 */
class TabelOpruimer implements Opruimer
{
    public function __construct(
        protected readonly string $tabel,
        protected readonly string $sleutelkolom = 'id',
        protected readonly int $venster = 10_000,
    ) {
    }

    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());

        $laagste = (int) DB::table($this->tabel)->min($this->sleutelkolom);
        $hoogste = (int) DB::table($this->tabel)->max($this->sleutelkolom);

        if ($hoogste === 0) {
            return 0;
        }

        $totaal = 0;
        $vanaf = $laagste;

        while ($vanaf <= $hoogste) {
            $tot = $vanaf + $this->venster - 1;

            $query = $this->basis($termijn, $grens)
                ->where($this->sleutelkolom, '>=', $vanaf)
                ->where($this->sleutelkolom, '<=', $tot);

            $totaal += $droog ? $query->count() : $query->delete();

            $vanaf = $tot + 1;

            // Voorbij het venster kan een gapend gat liggen. Springen naar de
            // eerstvolgende bestaande sleutel scheelt op een uitgedunde tabel
            // duizenden lege rondes.
            if ($vanaf <= $hoogste) {
                $volgende = DB::table($this->tabel)
                    ->where($this->sleutelkolom, '>=', $vanaf)
                    ->min($this->sleutelkolom);

                if ($volgende === null) {
                    break;
                }

                $vanaf = max($vanaf, (int) $volgende);
            }
        }

        return $totaal;
    }

    protected function basis(Termijn $termijn, Carbon $grens): Builder
    {
        $query = DB::table($this->tabel)->where($termijn->datumkolom(), '<', $grens);

        if ($filter = $termijn->filterClosure()) {
            $filter($query);
        }

        return $query;
    }
}

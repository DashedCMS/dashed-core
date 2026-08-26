<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Dashed\DashedCore\Retention\Contracts\Opruimer;
use Dashed\DashedCore\Retention\Contracts\FilterBewust;

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
class TabelOpruimer implements Opruimer, FilterBewust
{
    protected readonly int $venster;

    public function __construct(
        protected readonly string $tabel,
        protected readonly string $sleutelkolom = 'id',
        int $venster = 10_000,
    ) {
        // Een venster van nul of lager laat $tot niet voorbij $vanaf komen,
        // waardoor de sprong naar de eerstvolgende sleutel dezelfde $vanaf
        // teruggeeft en de lus nooit meer verder komt. Op een productietabel
        // is dat een oneindige lus, dus wordt hij hier hard op 1 geklemd.
        $this->venster = max(1, $venster);
    }

    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());

        $laagste = DB::table($this->tabel)->min($this->sleutelkolom);
        $hoogste = DB::table($this->tabel)->max($this->sleutelkolom);

        // Op null getoetst, niet op nul: een tabel waarvan de hoogste sleutel
        // toevallig 0 is (bijvoorbeeld een rij met id 0) is niet leeg, en
        // (int) null zou daar niet van te onderscheiden zijn.
        if ($hoogste === null) {
            return 0;
        }

        $laagste = (int) $laagste;
        $hoogste = (int) $hoogste;

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
        $query = DB::table($this->tabel);

        $termijn->pasVoorwaardenToe($query, $grens);

        return $query;
    }
}

<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Voor tabellen waarvan de sleutel een uuid is, zoals `notifications`. Een
 * uuid loopt niet met de tijd mee, dus een bereik erover betekent niets.
 * Daarom per portie eerst de sleutels ophalen en die daarna verwijderen. Dat
 * werkt op elke database, ook zonder DELETE ... LIMIT.
 */
class SleutelOpruimer implements Opruimer
{
    public function __construct(
        protected readonly string $tabel,
        protected readonly string $sleutelkolom = 'id',
    ) {
    }

    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());
        $totaal = 0;

        // Query\Builder::limit() zet bij 0 een LIMIT 0 (pluck() dan altijd
        // leeg, de lus stopt na de eerste ronde zonder iets te verwijderen)
        // en negeert een negatieve waarde stilzwijgend (geen limit, dus alle
        // rijen in één ongebreidelde query op een levende tabel), in beide
        // gevallen zonder foutmelding. Hard op 1 geklemd zodat een portie
        // altijd echt in porties verwerkt en de aanroeper geen stilzwijgend
        // niets-doen of onbedoeld ongelimiteerd verwijderen terugkrijgt.
        $portie = max(1, $portie);

        while (true) {
            $query = DB::table($this->tabel)->where($termijn->datumkolom(), '<', $grens);

            if ($filter = $termijn->filterClosure()) {
                $filter($query);
            }

            if ($droog) {
                return $query->count();
            }

            $sleutels = $query->limit($portie)->pluck($this->sleutelkolom);

            if ($sleutels->isEmpty()) {
                break;
            }

            $totaal += DB::table($this->tabel)
                ->whereIn($this->sleutelkolom, $sleutels)
                ->delete();
        }

        return $totaal;
    }
}

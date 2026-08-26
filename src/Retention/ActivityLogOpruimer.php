<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * De nieuwste regel per record blijft staan, want die voedt de kolom "laatst
 * bewerkt door" in de tabellen.
 *
 * Die bewaarlijst wordt eerst apart opgehaald en daarna in PHP afgetrokken,
 * en dat is geen omslachtigheid. MySQL weigert een subquery naar de tabel
 * waar je uit verwijdert (fout 1093), en SQLite staat dat wel toe: een filter
 * met zo'n subquery zou dus groen door de tests komen en pas op productie
 * klappen. Deze vorm is overgenomen uit het oude PruneActivityLogCommand.
 */
class ActivityLogOpruimer implements Opruimer
{
    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());

        $laatste = DB::table('activity_log')
            ->where($termijn->datumkolom(), '<', $grens)
            ->max('id');

        if ($laatste === null) {
            return 0;
        }

        $bewaren = $this->nieuwstePerOnderwerp();

        $aantal = 0;
        $vanaf = 0;

        while (true) {
            $ids = DB::table('activity_log')
                ->where('id', '>', $vanaf)
                ->where('id', '<=', $laatste)
                ->orderBy('id')
                ->limit($portie)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $vanaf = (int) $ids->last();
            $verwijderen = $ids->diff($bewaren)->all();

            if ($verwijderen === []) {
                continue;
            }

            if ($droog) {
                $aantal += count($verwijderen);

                continue;
            }

            $aantal += DB::table('activity_log')->whereIn('id', $verwijderen)->delete();
        }

        return $aantal;
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    protected function nieuwstePerOnderwerp(): \Illuminate\Support\Collection
    {
        // Bewust over de hele tabel en niet alleen over het te verwijderen
        // stuk: heeft een record sinds de grens nog iets gehad, dan is dat de
        // nieuwste regel en mag alles daarvoor gewoon weg. De twee
        // whereNotNull-guards zijn nodig omdat regels zonder onderwerp anders
        // in één groep vallen en daar willekeurig één van blijft staan.
        return DB::table('activity_log')
            ->whereNotNull('subject_type')
            ->whereNotNull('subject_id')
            ->groupBy('subject_type', 'subject_id')
            ->selectRaw('max(id) as id')
            ->pluck('id');
    }
}

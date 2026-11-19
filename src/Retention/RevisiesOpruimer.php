<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Dashed\DashedCore\Models\Revision;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Revisies verdwijnen na de termijn in dagen, en per record blijven hooguit
 * `dashed-core.revisions.max_per_record` gesloten revisies staan (standaard
 * 50, 0 = geen grens). Twee soorten blijven altijd: een open concept (draft
 * of pending, dat is werk van iemand) en de nieuwste live revisie van elk
 * record (anders is er niets meer om mee te vergelijken).
 */
class RevisiesOpruimer implements Opruimer
{
    public const STANDAARD_MAXIMUM = 50;

    public function __construct(protected readonly string $tabel = 'dashed__revisions')
    {
    }

    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        if (! Schema::hasTable($this->tabel)) {
            return 0;
        }

        $portie = max(1, $portie);
        $beschermd = $this->nieuwsteLiveIds();
        $grens = now()->subDays($termijn->dagen());

        $oud = fn () => DB::table($this->tabel)
            ->whereNotIn('status', Revision::OPEN_STATUSES)
            ->where('created_at', '<', $grens)
            ->when($beschermd !== [], fn ($q) => $q->whereNotIn('id', $beschermd));

        $aantal = $droog ? $oud()->count() : $this->verwijderInPorties($oud, $portie);

        return $aantal + $this->begrensPerRecord($beschermd, $portie, $droog);
    }

    /**
     * @return array<int, int>
     */
    protected function nieuwsteLiveIds(): array
    {
        return DB::table($this->tabel)
            ->whereIn('status', Revision::LIVE_STATUSES)
            ->groupBy('revisionable_type', 'revisionable_id')
            ->selectRaw('max(id) as id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function begrensPerRecord(array $beschermd, int $portie, bool $droog): int
    {
        $maximum = (int) config('dashed-core.revisions.max_per_record', self::STANDAARD_MAXIMUM);

        if ($maximum <= 0) {
            return 0;
        }

        $records = DB::table($this->tabel)
            ->whereNotIn('status', Revision::OPEN_STATUSES)
            ->groupBy('revisionable_type', 'revisionable_id')
            ->havingRaw('count(*) > ?', [$maximum])
            ->select('revisionable_type', 'revisionable_id')
            ->get();

        $aantal = 0;

        foreach ($records as $record) {
            $behouden = DB::table($this->tabel)
                ->where('revisionable_type', $record->revisionable_type)
                ->where('revisionable_id', $record->revisionable_id)
                ->whereNotIn('status', Revision::OPEN_STATUSES)
                ->orderByDesc('id')
                ->limit($maximum)
                ->pluck('id')
                ->merge($beschermd)
                ->all();

            $query = fn () => DB::table($this->tabel)
                ->where('revisionable_type', $record->revisionable_type)
                ->where('revisionable_id', $record->revisionable_id)
                ->whereNotIn('status', Revision::OPEN_STATUSES)
                ->whereNotIn('id', $behouden);

            $aantal += $droog ? $query()->count() : $this->verwijderInPorties($query, $portie);
        }

        return $aantal;
    }

    protected function verwijderInPorties(\Closure $query, int $portie): int
    {
        $aantal = 0;

        do {
            $ids = $query()->orderBy('id')->limit($portie)->pluck('id')->all();

            if ($ids === []) {
                break;
            }

            $aantal += DB::table($this->tabel)->whereIn('id', $ids)->delete();
        } while (count($ids) === $portie);

        return $aantal;
    }
}

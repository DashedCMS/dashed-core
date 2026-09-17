<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * De twee bewaartermijnen van `notifications` zijn een grens in dagen, en
 * dagen zeggen niets over aantallen: een afzender die ontspoort schrijft
 * binnen de termijn miljoenen rijen. Daarom bovenop de termijnen twee regels
 * die niet op leeftijd letten.
 *
 * Meldingen van een klant gaan helemaal weg. Een klant komt het paneel niet
 * in en ziet het belletje dus nooit; zo'n rij is er alleen omdat een afzender
 * naar te veel gebruikers stuurde (de printerbewaking deed dat tot september
 * 2026 naar iedereen).
 *
 * En per ontvanger blijven alleen de nieuwste N staan
 * (`dashed-core.notifications.max_per_user`, standaard 50, 0 = uit).
 *
 * PruneRunner roept een opruimer één keer per termijn aan. Het extra werk
 * hangt aan de harde termijn, anders draait het twee keer per ronde.
 */
class MeldingenOpruimer extends SleutelOpruimer
{
    public const STANDAARD_MAXIMUM = 50;

    /** @var array<int, string>|null */
    protected ?array $types = null;

    public function __construct(
        protected readonly string $extraBijTermijn = 'notifications',
    ) {
        parent::__construct('notifications');
    }

    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $aantal = parent::ruimOp($termijn, $portie, $droog);

        if ($termijn->sleutel() !== $this->extraBijTermijn) {
            return $aantal;
        }

        $portie = max(1, $portie);

        // De opruimer leeft zo lang als het proces (een queue worker dus
        // dagen), de types in de tabel niet.
        $this->types = null;

        // Bij een droge run kan een rij die al onder een termijn viel hier
        // nog een keer meetellen, want er is niets echt verwijderd. Het getal
        // is dan een bovengrens.
        return $aantal
            + $this->ruimKlantenOp($portie, $droog)
            + $this->begrensPerOntvanger($portie, $droog);
    }

    protected function ruimKlantenOp(int $portie, bool $droog): int
    {
        if ($this->gebruikerstypes() === [] || ! $this->kentRollen()) {
            return 0;
        }

        $query = fn (): Builder => $this->alleenKlanten(DB::table($this->tabel));

        if ($droog) {
            return $query()->count();
        }

        return $this->verwijderInPorties($query, $portie);
    }

    protected function begrensPerOntvanger(int $portie, bool $droog): int
    {
        $maximum = (int) config('dashed-core.notifications.max_per_user', self::STANDAARD_MAXIMUM);

        if ($maximum <= 0) {
            return 0;
        }

        // Loopt over de morph-index (notifiable_type, notifiable_id), dus ook
        // op een grote tabel een indexscan en geen sortering.
        //
        // Het klantfilter hoeft alleen bij een droge run: dan is er hierboven
        // niets verwijderd en zouden dezelfde rijen twee keer meetellen. In
        // een echte ronde zijn ze al weg, en dan is dit een subquery naar de
        // gebruikerstabel over elke overgebleven rij voor niets.
        $basis = DB::table($this->tabel);
        $teVeel = ($droog ? $this->zonderKlanten($basis) : $basis)
            ->select('notifiable_type', 'notifiable_id')
            ->selectRaw('count(*) as aantal')
            ->groupBy('notifiable_type', 'notifiable_id')
            ->havingRaw('count(*) > ?', [$maximum])
            ->get();

        if ($droog) {
            return (int) $teVeel->sum(fn ($rij) => (int) $rij->aantal - $maximum);
        }

        $totaal = 0;

        foreach ($teVeel as $rij) {
            $vanOntvanger = fn (): Builder => DB::table($this->tabel)
                ->where('notifiable_type', $rij->notifiable_type)
                ->where('notifiable_id', $rij->notifiable_id);

            // Eerst vastleggen wat blijft, daarna de rest weghalen. Een grens
            // op "ouder dan de vijftigste" breekt zodra rijen hetzelfde
            // tijdstip delen, en dat doen ze bij een bulk-insert altijd. De
            // sleutel als tweede sortering maakt de keuze bij gelijkstand
            // vast in plaats van willekeurig.
            $bewaren = $vanOntvanger()
                ->orderByDesc('created_at')
                ->orderByDesc($this->sleutelkolom)
                ->limit($maximum)
                ->pluck($this->sleutelkolom);

            $totaal += $this->verwijderInPorties(
                fn (): Builder => $vanOntvanger()->whereNotIn($this->sleutelkolom, $bewaren),
                $portie,
            );
        }

        return $totaal;
    }

    /**
     * Eerst de sleutels ophalen en die daarna verwijderen, zoals de
     * bovenliggende opruimer: werkt op elke database, ook zonder DELETE ... LIMIT.
     *
     * @param \Closure(): Builder $query
     */
    protected function verwijderInPorties(\Closure $query, int $portie): int
    {
        $totaal = 0;

        while (true) {
            $sleutels = $query()->limit($portie)->pluck($this->sleutelkolom);

            if ($sleutels->isEmpty()) {
                break;
            }

            $totaal += DB::table($this->tabel)
                ->whereIn($this->sleutelkolom, $sleutels)
                ->delete();
        }

        return $totaal;
    }

    protected function alleenKlanten(Builder $query): Builder
    {
        return $query
            ->whereIn('notifiable_type', $this->gebruikerstypes())
            ->whereIn('notifiable_id', $this->klantIds());
    }

    protected function zonderKlanten(Builder $query): Builder
    {
        if ($this->gebruikerstypes() === [] || ! $this->kentRollen()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereNotIn('notifiable_type', $this->gebruikerstypes())
            ->orWhereNotIn('notifiable_id', $this->klantIds()));
    }

    /** Een subquery naar een andere tabel, dus geen MySQL-fout 1093. */
    protected function klantIds(): Builder
    {
        return DB::table((new User())->getTable())->where('role', 'customer')->select('id');
    }

    protected function kentRollen(): bool
    {
        return Schema::hasColumn((new User())->getTable(), 'role');
    }

    /**
     * Een klantproject gebruikt zijn eigen App\Models\User, die van de onze
     * erft, en soms een morph-map. Daarom de types uit de tabel zelf lezen en
     * toetsen of ze een gebruiker zijn, in plaats van één klassenaam aannemen.
     *
     * @return array<int, string>
     */
    protected function gebruikerstypes(): array
    {
        return $this->types ??= DB::table($this->tabel)
            ->distinct()
            ->pluck('notifiable_type')
            ->filter(fn ($type): bool => is_string($type)
                && is_a(Relation::getMorphedModel($type) ?? $type, User::class, true))
            ->values()
            ->all();
    }
}

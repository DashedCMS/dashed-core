<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Dashed\DashedCore\Retention\Contracts\Opruimer;
use Dashed\DashedCore\Retention\Contracts\FilterBewust;

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
     * @return array<int, array{sleutel: string, label: string, aantal: int, fout: ?string, overgeslagen: bool, reden: ?string, haak_overgeslagen: bool}>
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
     * @return array{sleutel: string, label: string, aantal: int, fout: ?string, overgeslagen: bool, reden: ?string, haak_overgeslagen: bool}
     */
    protected function draaiEen(Retention $retention, int $portie, bool $droog): array
    {
        $regel = [
            'sleutel' => $retention->sleutel(),
            'label' => $retention->labelTekst(),
            'aantal' => 0,
            'fout' => null,
            'overgeslagen' => false,
            'reden' => null,
            'haak_overgeslagen' => false,
        ];

        // Niet elk pakket staat op elke installatie, en deze monorepo zelf
        // heeft ook geen migratie voor `failed_jobs`. Een tabel die ontbreekt
        // is geen fout: dan bestaat dat logboek hier gewoon niet. Alleen
        // getoetst als de entry überhaupt een tabel opgeeft; een entry met
        // een eigen opruimer en zonder tabel heeft niets om te controleren en
        // wordt dus nooit om deze reden overgeslagen.
        $tabel = $retention->tabelNaam();

        if ($tabel !== null && ! Schema::hasTable($tabel)) {
            return [...$regel, 'overgeslagen' => true, 'reden' => 'tabel bestaat niet op deze installatie'];
        }

        // De haak draait voor de eerste verwijdering. Faalt hij, dan blijft
        // alles staan: half opruimen is erger dan niet opruimen, want de
        // cijfers die de haak moest veiligstellen zijn dan al weg. Dit is wel
        // een fout: de tabel bestaat wel, maar iets in de eigen logica van de
        // haak liep stuk.
        //
        // Bij een droge run blijft hij staan. Een haak stelt cijfers veilig
        // voordat rijen verdwijnen, en bij een droge run verdwijnt er niets,
        // dus valt er ook niets veilig te stellen. Hij is bovendien niet
        // alleen-lezen: die van popup_views verwijdert en schrijft in de
        // dagaggregatie na een ontdekkingsquery over de hele tabel. Wie eerst
        // voorzichtig wil tellen mag daar niet de zwaarste schrijfronde van
        // het systeem mee losmaken.
        if (($haak = $retention->voorafHaak()) && $droog) {
            // Alleen de haak vervalt, het tellen niet: een droge run hoort te
            // laten zien hoeveel er zou verdwijnen.
            $haak = null;
            $regel['haak_overgeslagen'] = true;
        }

        if ($haak) {
            try {
                $haak($this->dekkingsGrens($retention));
            } catch (Throwable $e) {
                report($e);

                return [...$regel, 'fout' => $e->getMessage(), 'overgeslagen' => true];
            }
        }

        try {
            $opruimer = $this->opruimerVoor($retention, $portie);

            foreach ($retention->termijnen() as $termijn) {
                $regel['aantal'] += $opruimer->ruimOp($termijn, $portie, $droog);
            }
        } catch (Throwable $e) {
            report($e);
            $regel['fout'] = $e->getMessage();
        }

        return $regel;
    }

    protected function opruimerVoor(Retention $retention, int $portie): Opruimer
    {
        if ($eigen = $retention->eigenOpruimer()) {
            $this->weigerGenegeerdFilter($retention, $eigen);

            return $eigen;
        }

        $tabel = $retention->tabelNaam();

        if ($tabel === null) {
            throw new \RuntimeException(
                'Retention "' . $retention->sleutel() . '" heeft geen tabel en geen eigen opruimer.'
            );
        }

        // De portie is ook de venstergrootte. Zonder dit staat het venster
        // vast op de standaard van de constructor en doet --chunk niets voor
        // de acht entries die op deze opruimer leunen, terwijl het command de
        // optie wel aanbiedt.
        return new TabelOpruimer($tabel, $retention->tabelKolom(), $portie);
    }

    /**
     * Een filter is een behoudregel: het zegt welke rijen ondanks hun leeftijd
     * moeten blijven staan. Een opruimer die dat filter niet leest verwijdert
     * ze alsnog, en dat is precies het soort verlies dat niemand opmerkt
     * voordat het te laat is. Daarom klapt de entry hier hardop in plaats van
     * stil door te draaien; hetzelfde geldt voor een terugvalkolom, want die
     * verruimt juist wat er weg mag.
     */
    protected function weigerGenegeerdFilter(Retention $retention, Opruimer $opruimer): void
    {
        if ($opruimer instanceof FilterBewust) {
            return;
        }

        foreach ($retention->termijnen() as $termijn) {
            if ($termijn->filterClosure() === null && $termijn->terugvalkolomNaam() === null) {
                continue;
            }

            throw new \RuntimeException(
                'Retention "' . $retention->sleutel() . '" draagt een filter of terugvalkolom op termijn "'
                . $termijn->sleutel() . '", maar ' . $opruimer::class . ' past die niet toe.'
            );
        }
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

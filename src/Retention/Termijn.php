<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Closure;
use Carbon\Carbon;
use RuntimeException;
use Dashed\DashedCore\Models\Customsetting;

/**
 * Eén bewaartermijn. Een tabel kan er meer dan één hebben: meldingen
 * verdwijnen veertien dagen na lezen en zestig dagen na binnenkomst, gemeten
 * vanaf verschillende kolommen.
 */
final class Termijn
{
    private string|Closure $label = '';

    private string|Closure|null $uitleg = null;

    private ?Closure $filter = null;

    private ?string $instellingssleutel = null;

    private string $eenheid = 'dagen';

    private ?string $minstens = null;

    private ?string $terugvalkolom = null;

    private function __construct(
        private readonly string $sleutel,
        private readonly int|Closure $standaard,
        private readonly string $datumkolom,
    ) {
    }

    public static function make(string $sleutel, int|Closure $standaard, string $datumkolom): self
    {
        return new self($sleutel, $standaard, $datumkolom);
    }

    public function label(string|Closure $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function uitleg(string|Closure $uitleg): self
    {
        $this->uitleg = $uitleg;

        return $this;
    }

    public function filter(Closure $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Alleen nodig voor termijnen waarvan de instelling al onder een andere
     * naam op productieservers staat. Zonder deze uitzondering zou het nieuwe
     * sleutelformaat die ingevulde waarden stilzwijgend laten vallen.
     */
    public function instellingssleutel(string $naam): self
    {
        $this->instellingssleutel = $naam;

        return $this;
    }

    /**
     * Vrijwel elke termijn telt dagen. Sitescans tellen scans: daar blijven de
     * laatste N scans volledig staan, ongeacht hoe oud ze zijn. Het scherm
     * leest dit om het achtervoegsel en de bovengrens van het veld te kiezen.
     */
    public function eenheid(string $eenheid): self
    {
        $this->eenheid = $eenheid;

        return $this;
    }

    public function eenheidNaam(): string
    {
        return $this->eenheid;
    }

    /**
     * Legt vast dat deze termijn minstens zo lang moet zijn als een andere
     * termijn, zoals de harde meldingentermijn die nooit korter mag zijn dan
     * de termijn na lezen. Andersom zou een net gelezen melding eerder
     * verdwijnen dan een die niemand ooit opende.
     *
     * De sleutel verwijst naar de andere Termijn::sleutel(), niet naar zijn
     * instellingssleutel: die laatste is een implementatiedetail van hoe hij
     * wordt opgeslagen en kan per omgeving verschillen.
     */
    public function minstens(string $andereTermijnSleutel): self
    {
        $this->minstens = $andereTermijnSleutel;

        return $this;
    }

    public function minstensTermijn(): ?string
    {
        return $this->minstens;
    }

    public function sleutel(): string
    {
        return $this->sleutel;
    }

    public function datumkolom(): string
    {
        return $this->datumkolom;
    }

    /**
     * De kolom waarop teruggevallen wordt als de datumkolom leeg mag zijn. Een
     * vergelijking laat NULL altijd buiten beeld, dus zonder terugval blijft
     * zo'n rij voor altijd staan: precies de rijen die niemand meer bekijkt.
     */
    public function terugvalkolom(string $kolom): self
    {
        $this->terugvalkolom = $kolom;

        return $this;
    }

    public function terugvalkolomNaam(): ?string
    {
        return $this->terugvalkolom;
    }

    public function filterClosure(): ?Closure
    {
        return $this->filter;
    }

    /**
     * Zet de datumgrens en het eventuele filter op een query. Staat hier en
     * niet in elke opruimer apart, omdat de haakjes eromheen ertoe doen: een
     * opruimer plakt hierna nog eigen voorwaarden aan dezelfde query, en een
     * losse orWhere zou daar dwars doorheen binden en rijen buiten het venster
     * meenemen.
     */
    public function pasVoorwaardenToe(mixed $query, Carbon $grens): void
    {
        if ($this->terugvalkolom === null) {
            $query->where($this->datumkolom, '<', $grens);
        } else {
            $query->where(function ($groep) use ($grens) {
                $groep->where($this->datumkolom, '<', $grens)
                    ->orWhere(fn ($terugval) => $terugval
                        ->whereNull($this->datumkolom)
                        ->where($this->terugvalkolom, '<', $grens));
            });
        }

        if ($this->filter) {
            ($this->filter)($query);
        }
    }

    public function instellingssleutelNaam(): string
    {
        return $this->instellingssleutel ?? $this->sleutel . '_retention_days';
    }

    public function standaardDagen(): int
    {
        return $this->standaard instanceof Closure ? (int) ($this->standaard)() : $this->standaard;
    }

    /**
     * De canonieke lezer van de ingestelde waarde, in de eenheid van deze
     * termijn (zie eenheid()). Voor de meeste termijnen zijn dat dagen, maar
     * niet voor allemaal: sitescans tellen scans. Gebruik deze naam waar de
     * eenheid uitmaakt of niet op voorhand dagen is, zodat er niet per
     * ongeluk "dagen" gelezen wordt waar het een ander soort telling is.
     *
     * Een waarde van nul of lager zou alles verwijderen tot en met wat er
     * zojuist binnenkwam. Dat is nooit wat iemand met een leeg of stukgetypt
     * veld bedoelt, dus valt hij dan terug op de standaard.
     *
     * Is die standaard zelf onbruikbaar, dan is er niets meer om op terug te
     * vallen en gooit deze methode. Bewust een uitzondering en niet een klem
     * op 1: een standaard komt uit code, config of een env-variabele en is dus
     * altijd een fout van de installatie, nooit een vertypte invoer. En
     * afklemmen op 1 zou die fout ook niet redden, want een grens van gisteren
     * is net zo goed de hele tabel weg. Een lege regel
     * DASHED_SENT_EMAILS_RETENTION_DAYS= geeft (int) '' en dus nul; die moet
     * het opruimen laten falen, niet stilzwijgend het logboek wissen.
     * PruneRunner vangt dit per entry op, dus de rest van het register loopt
     * gewoon door en het command eindigt op een foutcode.
     */
    public function waarde(): int
    {
        $standaard = $this->standaardDagen();
        // Customsetting::get() accepteert null|string|array als standaard; door
        // strict_types hierboven moet het gehele getal dus als string mee.
        $waarde = (int) Customsetting::get($this->instellingssleutelNaam(), null, (string) $standaard);

        if ($waarde >= 1) {
            return $waarde;
        }

        if ($standaard < 1) {
            throw new RuntimeException(
                'Bewaartermijn "' . $this->sleutel . '" heeft een standaard van ' . $standaard
                . ' en een onbruikbare instelling (' . $this->instellingssleutelNaam()
                . '). Zonder geldige termijn zou de grens op nu liggen en zou alles verdwijnen.'
            );
        }

        return $standaard;
    }

    /**
     * Dunne alias van waarde(). Gebruik dagen() waar de eenheid van deze
     * termijn daadwerkelijk dagen is, dat leest het duidelijkst op de
     * aanroepplek. Gebruik waarde() waar de eenheid iets anders is (zoals
     * scans) of niet vaststaat.
     */
    public function dagen(): int
    {
        return $this->waarde();
    }

    public function labelTekst(): string
    {
        return $this->label instanceof Closure ? (string) ($this->label)() : $this->label;
    }

    public function uitlegTekst(): ?string
    {
        if ($this->uitleg === null) {
            return null;
        }

        return $this->uitleg instanceof Closure ? (string) ($this->uitleg)() : $this->uitleg;
    }
}

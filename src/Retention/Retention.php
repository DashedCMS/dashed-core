<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Closure;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Eén opruimbare tabel: waar hij staat, wie hem bezit, en met welke termijnen
 * hij opgeruimd wordt.
 */
final class Retention
{
    private string|Closure $label = '';

    private string $pakket = 'dashed-core';

    private string|Closure|null $pakketLabel = null;

    private ?string $tabel = null;

    private string $sleutelkolom = 'id';

    /** @var array<int, Termijn> */
    private array $termijnen = [];

    private ?Opruimer $opruimer = null;

    private ?Closure $vooraf = null;

    private function __construct(private readonly string $sleutel)
    {
    }

    public static function make(string $sleutel): self
    {
        return new self($sleutel);
    }

    public function label(string|Closure $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * $pakket is de slug waarop het scherm groepeert (stabiel, nooit vertaald).
     * $label is wat de sectiekop toont; zonder label valt de kop terug op de
     * slug, wat de vorige situatie was voor pakketten die nog niets opgeven.
     */
    public function pakket(string $pakket, string|Closure|null $label = null): self
    {
        $this->pakket = $pakket;
        $this->pakketLabel = $label;

        return $this;
    }

    public function tabel(string $tabel): self
    {
        $this->tabel = $tabel;

        return $this;
    }

    /**
     * De kolom waarover het opruimen in vensters loopt. Standaard `id`, want
     * dat is een oplopend geheel getal dat met de tijd meeloopt. Een tabel met
     * een uuid als sleutel geeft die kolom ook op, maar krijgt een andere
     * opruimer omdat een uuid-bereik niets betekent.
     */
    public function sleutelkolom(string $kolom): self
    {
        $this->sleutelkolom = $kolom;

        return $this;
    }

    public function termijn(Termijn $termijn): self
    {
        $this->termijnen[] = $termijn;

        return $this;
    }

    public function opruimer(Opruimer $opruimer): self
    {
        $this->opruimer = $opruimer;

        return $this;
    }

    /**
     * Draait met de berekende grensdatum voordat er ook maar één rij
     * verdwijnt. Popup-vertoningen gebruiken dit om de dagaggregatie bij te
     * werken: zonder die aggregatie verdwijnen de cijfers met de rijen mee.
     */
    public function vooraf(Closure $haak): self
    {
        $this->vooraf = $haak;

        return $this;
    }

    public function sleutel(): string
    {
        return $this->sleutel;
    }

    public function labelTekst(): string
    {
        return $this->label instanceof Closure ? (string) ($this->label)() : $this->label;
    }

    public function pakketNaam(): string
    {
        return $this->pakket;
    }

    public function pakketLabel(): string
    {
        if ($this->pakketLabel === null) {
            return $this->pakket;
        }

        return $this->pakketLabel instanceof Closure ? (string) ($this->pakketLabel)() : $this->pakketLabel;
    }

    public function tabelNaam(): ?string
    {
        return $this->tabel;
    }

    public function tabelKolom(): string
    {
        return $this->sleutelkolom;
    }

    /** @return array<int, Termijn> */
    public function termijnen(): array
    {
        return $this->termijnen;
    }

    public function eigenOpruimer(): ?Opruimer
    {
        return $this->opruimer;
    }

    public function voorafHaak(): ?Closure
    {
        return $this->vooraf;
    }
}

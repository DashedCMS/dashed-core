<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Closure;
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

    public function sleutel(): string
    {
        return $this->sleutel;
    }

    public function datumkolom(): string
    {
        return $this->datumkolom;
    }

    public function filterClosure(): ?Closure
    {
        return $this->filter;
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
     * Een termijn van nul of lager zou alles verwijderen tot en met wat er
     * zojuist binnenkwam. Dat is nooit wat iemand met een leeg of stukgetypt
     * veld bedoelt, dus valt hij dan terug op de standaard.
     */
    public function dagen(): int
    {
        $standaard = $this->standaardDagen();
        // Customsetting::get() accepteert null|string|array als standaard; door
        // strict_types hierboven moet het gehele getal dus als string mee.
        $dagen = (int) Customsetting::get($this->instellingssleutelNaam(), null, (string) $standaard);

        return $dagen >= 1 ? $dagen : $standaard;
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

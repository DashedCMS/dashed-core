<?php

namespace Dashed\DashedCore\Filament\Fields;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Set;

class Numpad extends Field
{
    protected string $view = 'dashed-core::fields.numpad';

    /** Config */
    protected bool $storesCents = false;        // DB als euro's (DECIMAL) = false, als centen (INT) = true
    protected string $currencySymbol = '€';
    protected bool $allowNegative = false;
    protected ?int $minCents = 0;
    protected ?int $maxCents = null;
    protected ?string $displayLabel = null;

    public function currency(string $symbol): static { $this->currencySymbol = $symbol; return $this; }
    public function storesCents(bool $value = false): static { $this->storesCents = $value; return $this; }
    public function allowNegative(bool $value = true): static { $this->allowNegative = $value; return $this; }
    public function minEuro(float|int $euros): static { $this->minCents = (int) round($euros * 100); return $this; }
    public function maxEuro(float|int $euros): static { $this->maxCents = (int) round($euros * 100); return $this; }
    public function minCents(?int $cents): static { $this->minCents = $cents; return $this; }
    public function maxCents(?int $cents): static { $this->maxCents = $cents; return $this; }
    public function displayLabel(?string $label): static { $this->displayLabel = $label; return $this; }

    protected function setUp(): void
    {
        parent::setUp();

        // Zorg dat de field ALTIJD wordt meegestuurd
        $this->dehydrated(true);

        // Voorkom null tijdens eerste render
        // - bij storesCents=true => 0 (centen)
        // - bij storesCents=false => 0.00 (euro's)
        $this->default(fn () => $this->storesCents ? 0 : 0.00);

        $this->afterStateHydrated(function (Set $set, $state) {
            if ($this->storesCents) {
                // Intern centen-int
                if ($state === null || $state === '') {
                    $set($this->getStatePath(), 0);
                    return;
                }
                // "12.34" → 1234 (als iemand euro-string in DB had)
                if (is_string($state) && str_contains($state, '.')) {
                    $state = (int) round(((float) $state) * 100);
                }
                $set($this->getStatePath(), (int) $state);
            } else {
                // Intern euro-decimaal (2 cijfers)
                $set($this->getStatePath(), round((float) ($state ?? 0), 2));
            }
        });

        // 🎯 Belangrijk: GEEN extra /100 meer als je euro’s opslaat.
        $this->dehydrateStateUsing(function ($state) {
            if ($this->storesCents) {
                // entangled = centen (int)
                return (int) ($state ?? 0);
            }
            // entangled = euro's (string/float). Laat zo.
            return is_numeric($state) ? round((float) $state, 2) : 0.00;
        });

        // Server-side min/max
        $this->rule(function () {
            return function (string $attribute, $value, Closure $fail) {
                // Valideer altijd in centen
                $cents = $this->storesCents
                    ? (int) $value
                    : (int) round(((float) $value) * 100);

                if ($this->minCents !== null && $cents < $this->minCents) {
                    $fail(__('Het bedrag mag niet lager zijn dan :amount.', [
                        'amount' => $this->formatEuro($this->minCents),
                    ]));
                }

                if ($this->maxCents !== null && $cents > $this->maxCents) {
                    $fail(__('Het bedrag mag niet hoger zijn dan :amount.', [
                        'amount' => $this->formatEuro($this->maxCents),
                    ]));
                }

                if (! $this->allowNegative && $cents < 0) {
                    $fail(__('Negatieve bedragen zijn niet toegestaan.'));
                }
            };
        });
    }

    protected function formatEuro(int $cents): string
    {
        $euros = floor(abs($cents) / 100);
        $centPart = str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
        $eurosStr = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $euros);
        $sign = $cents < 0 ? '-' : '';

        return "{$sign}{$this->currencySymbol} {$eurosStr},{$centPart}";
    }

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'storesCents'    => $this->storesCents,
            'currencySymbol' => $this->currencySymbol,
            'allowNegative'  => $this->allowNegative,
            'minCents'       => $this->minCents,
            'maxCents'       => $this->maxCents,
            'displayLabel'   => $this->displayLabel ?? $this->getLabel(),
        ]);
    }
}

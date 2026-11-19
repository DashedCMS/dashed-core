<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Classes\AbTests;

/**
 * Een eenvoudige z-toets op twee proporties (gepoolde standaardfout,
 * tweezijdig). Bewust simpel: genoeg om te zien of een verschil toeval kan
 * zijn, geen vervanging voor een statisticus. Onder de 30 vertoningen per
 * variant of zonder enig succes doen we geen uitspraak.
 */
class Significance
{
    public const MIN_VIEWS = 30;

    public const ALPHA = 0.05;

    /**
     * @return array{z: float|null, p: float|null, significant: bool, leader: string|null}
     */
    public static function twoProportion(int $successA, int $viewsA, int $successB, int $viewsB): array
    {
        $empty = ['z' => null, 'p' => null, 'significant' => false, 'leader' => null];

        if ($viewsA < self::MIN_VIEWS || $viewsB < self::MIN_VIEWS) {
            return $empty;
        }

        $successA = min($successA, $viewsA);
        $successB = min($successB, $viewsB);
        $pA = $successA / $viewsA;
        $pB = $successB / $viewsB;
        $pooled = ($successA + $successB) / ($viewsA + $viewsB);
        $se = sqrt($pooled * (1 - $pooled) * (1 / $viewsA + 1 / $viewsB));

        if ($se <= 0.0) {
            return $empty;
        }

        $z = ($pB - $pA) / $se;
        $p = 2 * (1 - self::normalCdf(abs($z)));

        return [
            'z' => round($z, 3),
            'p' => round($p, 4),
            'significant' => $p < self::ALPHA,
            'leader' => $pA === $pB ? null : ($pB > $pA ? 'b' : 'a'),
        ];
    }

    /** Standaardnormale verdelingsfunctie via de benadering van Abramowitz en Stegun (7.1.26). */
    public static function normalCdf(float $x): float
    {
        $t = 1 / (1 + 0.3275911 * abs($x) / M_SQRT2);
        $erf = 1 - (((((1.061405429 * $t - 1.453152027) * $t) + 1.421413741) * $t - 0.284496736) * $t + 0.254829592) * $t * exp(-($x * $x) / 2);
        $erf = $x >= 0 ? $erf : -$erf;

        return 0.5 * (1 + $erf);
    }
}

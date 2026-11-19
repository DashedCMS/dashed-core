<?php

namespace Dashed\DashedCore\Reports;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Een bron voor de rapportbouwer. Een pakket meldt zijn bronnen aan met
 * `cms()->builder('reportSources', [MijnBron::class])` en bepaalt daarmee
 * alles wat een beheerder kan kiezen: meetwaarden, groeperingen en filters
 * zijn sleutels naar SQL die de bron zelf schrijft. Er komt nooit vrije SQL
 * of een vrije kolomnaam uit het formulier in de query.
 */
abstract class ReportSource
{
    abstract public function key(): string;

    abstract public function label(): string;

    /** De basisquery, met de vaste afbakening (betaalde orders, deze site, ...). */
    abstract public function query(): Builder;

    /** Kolom waarop periode en datumgroepering werken, als `tabel.kolom`. */
    abstract public function dateColumn(): string;

    /** @return array<string, ReportMeasure> */
    abstract public function measures(): array;

    /** @return array<string, ReportGrouping> */
    public function groupings(): array
    {
        return [
            'day' => ReportGrouping::period(__('Per dag'), 'day'),
            'week' => ReportGrouping::period(__('Per week'), 'week'),
            'month' => ReportGrouping::period(__('Per maand'), 'month'),
            'year' => ReportGrouping::period(__('Per jaar'), 'year'),
        ];
    }

    /** @return array<string, ReportFilter> */
    public function filters(): array
    {
        return [];
    }

    /**
     * Joins die meetwaarden, groeperingen en filters kunnen vragen, op naam.
     * Elke join wordt hooguit één keer toegepast.
     *
     * @return array<string, Closure(Builder): void>
     */
    public function joins(): array
    {
        return [];
    }

    /** Het recht dat een beheerder nodig heeft om deze bron te gebruiken. */
    public function permission(): ?string
    {
        return null;
    }

    public function canBeUsedBy($user): bool
    {
        $permission = $this->permission();

        return $permission === null || ($user && $user->can($permission));
    }
}

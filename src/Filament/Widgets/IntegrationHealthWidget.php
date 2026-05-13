<?php

namespace Dashed\DashedCore\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Dashed\DashedCore\Enums\IntegrationStatus;
use Dashed\DashedCore\Filament\Pages\IntegrationsDashboard;
use Dashed\DashedCore\Integrations\IntegrationHealthRunner;

/**
 * One-stat widget on the admin home that surfaces the count of currently
 * failing or misconfigured integrations. Click-through to the
 * IntegrationsDashboard for details. Hidden when the current user can't
 * see the dashboard (mirrors `IntegrationsDashboard::canAccess()`).
 */
class IntegrationHealthWidget extends StatsOverviewWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 99;

    public static function canView(): bool
    {
        return IntegrationsDashboard::canAccess();
    }

    protected function getStats(): array
    {
        $registry = cms()->integrationRegistry();
        $runner = app(IntegrationHealthRunner::class);

        $failing = 0;
        foreach ($registry->all() as $definition) {
            $health = $runner->run($definition);
            if (in_array($health->status, [IntegrationStatus::Failing, IntegrationStatus::Misconfigured], true)) {
                $failing++;
            }
        }

        $color = $failing > 0 ? 'danger' : 'gray';
        $description = $failing > 0
            ? 'Klik door om de cards te bekijken'
            : 'Alle integraties draaien';

        $stat = Stat::make('Falende integraties', (string) $failing)
            ->description($description)
            ->color($color);

        if (method_exists($stat, 'url')) {
            try {
                $stat = $stat->url(IntegrationsDashboard::getUrl());
            } catch (\Throwable) {
                // getUrl() can throw outside an active panel; ignore.
            }
        }

        return [$stat];
    }
}

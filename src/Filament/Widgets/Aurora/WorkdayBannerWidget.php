<?php

namespace Dashed\DashedCore\Filament\Widgets\Aurora;

use Filament\Widgets\Widget;
use Dashed\DashedCore\Dashboard\DashboardPriorities;

/**
 * "Jouw werkdag in één oogopslag": het aantal open prioriteiten uit het
 * register, met een knop naar het blok "Heeft aandacht nodig". Zonder open
 * prioriteiten rendert de widget niets.
 */
class WorkdayBannerWidget extends Widget
{
    protected string $view = 'dashed-core::filament.widgets.aurora.workday-banner';

    protected int|string|array $columnSpan = 'full';

    public function getOpenCount(): int
    {
        return DashboardPriorities::count();
    }
}

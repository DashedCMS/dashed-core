<?php

namespace Dashed\DashedCore\Filament\Widgets\Aurora;

use Filament\Widgets\Widget;
use Dashed\DashedCore\Dashboard\DashboardPriorities;

/**
 * "Heeft aandacht nodig": de open prioriteiten uit
 * cms()->builder('dashboardPriorities'), gesorteerd en begrensd.
 */
class PriorityQueueWidget extends Widget
{
    public const LIMIT = 6;

    protected string $view = 'dashed-core::filament.widgets.aurora.priority-queue';

    protected int|string|array $columnSpan = 'full';

    /** @return array{items: array<int, array>, total: int} */
    public function getQueue(): array
    {
        $all = DashboardPriorities::all();

        return [
            'items' => array_slice($all, 0, self::LIMIT),
            'total' => count($all),
        ];
    }
}

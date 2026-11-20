<?php

namespace Dashed\DashedCore\Navigation;

use Filament\Navigation\NavigationManager;
use Dashed\DashedCore\Classes\NavigationUsage;

/**
 * Filaments eigen navigatieopbouw, met daarna per menukopje de volgorde van
 * de ingelogde beheerder (zie NavigationUsage). Gebonden in de ServiceProvider
 * in plaats van Filaments scoped binding, zodat zijbalk, zoekpopup en alles
 * wat Filament::getNavigation() leest dezelfde volgorde zien.
 */
class UsageSortedNavigationManager extends NavigationManager
{
    public function get(): array
    {
        return NavigationUsage::sort(parent::get());
    }
}

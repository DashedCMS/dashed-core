<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Dashed\DashedCore\Classes\SystemHealth;
use Dashed\DashedCore\Traits\HasSettingsPermission;

class SystemStatusPage extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Systeemstatus';

    protected string $view = 'dashed-core::settings.pages.system-status';

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public function items(): array
    {
        return SystemHealth::run(request());
    }

    public function recheckMigrations(): void
    {
        SystemHealth::forgetMigrations();
    }
}

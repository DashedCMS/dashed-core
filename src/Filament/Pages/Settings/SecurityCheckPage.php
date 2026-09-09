<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Dashed\DashedCore\Security\SecurityCheck;
use Dashed\DashedCore\Traits\HasSettingsPermission;

class SecurityCheckPage extends Page
{
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Beveiligingscheck';

    protected string $view = 'dashed-core::settings.pages.security-check';

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public function items(): array
    {
        return SecurityCheck::run(request());
    }
}

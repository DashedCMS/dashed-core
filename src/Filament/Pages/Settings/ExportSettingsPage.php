<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

/**
 * De bewaartermijn van exports stond hier en staat nu bij Opschonen, waar alle
 * termijnen bij elkaar staan. Twee schermen op dezelfde instellingssleutel
 * geven twee verschillende uitlegteksten en overschrijven elkaars waarde.
 */
class ExportSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Export instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Text::make(__('De bewaartermijn van exports staat nu bij Instellingen, Opschonen, samen met de termijnen van alle andere logboeken.')),
        ])->statePath('data');
    }

    public function submit(): void
    {
        redirect(CleanupSettingsPage::getUrl());
    }
}

<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

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
        $this->form->fill([
            'exports_retention_days' => (int) Customsetting::get('exports_retention_days', null, 365),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('exports_retention_days')
                ->label('Bewaartermijn (dagen)')
                ->helperText('Exports ouder dan dit aantal dagen worden automatisch verwijderd. Standaard: 365 dagen (1 jaar).')
                ->numeric()
                ->minValue(1)
                ->maxValue(3650)
                ->required(),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            Customsetting::set('exports_retention_days', (int) $formData['exports_retention_days'], $site['id']);
        }

        Notification::make()
            ->title('Export instellingen opgeslagen')
            ->success()
            ->send();

        redirect(ExportSettingsPage::getUrl());
    }
}

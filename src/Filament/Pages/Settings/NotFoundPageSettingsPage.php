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

class NotFoundPageSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = '404-pagina instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'not_found_page_occurrences_retention_days' => (int) Customsetting::get('not_found_page_occurrences_retention_days', null, 30),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('not_found_page_occurrences_retention_days')
                ->label('Bewaartermijn 404-bezoeken (dagen)')
                ->helperText('Individuele 404-bezoeken (occurrences) ouder dan dit aantal dagen worden automatisch verwijderd. De not-found-pagina-records zelf blijven behouden; alleen hun bezoek-historie wordt opgeschoond. Standaard: 30 dagen.')
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
            Customsetting::set('not_found_page_occurrences_retention_days', (int) $formData['not_found_page_occurrences_retention_days'], $site['id']);
        }

        Notification::make()
            ->title('404-pagina instellingen opgeslagen')
            ->success()
            ->send();

        redirect(NotFoundPageSettingsPage::getUrl());
    }
}

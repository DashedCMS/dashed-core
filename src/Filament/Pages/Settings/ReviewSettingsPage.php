<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedPages\Models\Page as PageModel;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class ReviewSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Review';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["review_overview_page_id_{$site['id']}"] = Customsetting::get('review_overview_page_id', $site['id']);
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $newSchema = [
                Select::make("review_overview_page_id_{$site['id']}")
                    ->label('Review pagina')
                    ->searchable()
                    ->preload()
                    ->options(PageModel::thisSite($site['id'])->pluck('name', 'id')),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($newSchema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $schema->schema($tabGroups)
            ->statePath('data');
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('review_overview_page_id', $this->form->getState()["review_overview_page_id_{$site['id']}"], $site['id']);
        }

        Notification::make()
            ->title('De review instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(ReviewSettingsPage::getUrl());
    }
}

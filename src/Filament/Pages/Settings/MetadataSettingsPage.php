<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Tabs\Tab;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Forms\Concerns\InteractsWithForms;

class MetadataSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Meta data instellingen';
    protected static ?string $navigationGroup = 'Overige';
    protected static ?string $title = 'Meta data instellingen';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["default_meta_data_twitter_site_{$site['id']}"] = Customsetting::get('default_meta_data_twitter_site', $site['id']);
            $formData["default_meta_data_twitter_creator_{$site['id']}"] = Customsetting::get('default_meta_data_twitter_creator', $site['id']);
            $formData["default_meta_data_image_{$site['id']}"] = Customsetting::get('default_meta_data_image', $site['id']);
        }

        $this->form->fill($formData);
    }

    protected function getFormSchema(): array
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("Meta data voor {$site['name']}")
                    ->content('Dit is de standaard voor meta data.'),
                TextInput::make("default_meta_data_twitter_site_{$site['id']}")
                    ->label('Twitter site')
                    ->rules([
                        'max:255',
                    ])
                    ->helperText('Bijv: @dashed.dev'),
                TextInput::make("default_meta_data_twitter_creator_{$site['id']}")
                    ->label('Twitter creator')
                    ->rules([
                        'max:255',
                    ])
                    ->helperText('Bijv: @dashed.dev'),
                FileUpload::make("default_meta_data_image_{$site['id']}")
                    ->label('Meta image')
                    ->directory('dashed/metadata')
                    ->image()
                    ->rules([
                        'image',
                    ])
                    ->helperText('Dit is de placeholder meta afbeelding die gebruikt wordt als er geen meta afbeelding is opgegeven.'),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($schema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $tabGroups;
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('default_meta_data_twitter_site', $this->form->getState()["default_meta_data_twitter_site_{$site['id']}"], $site['id']);
            Customsetting::set('default_meta_data_twitter_creator', $this->form->getState()["default_meta_data_twitter_creator_{$site['id']}"], $site['id']);
            Customsetting::set('default_meta_data_image', $this->form->getState()["default_meta_data_image_{$site['id']}"], $site['id']);
        }

        Cache::tags(['custom-settings'])->flush();

        $this->notify('success', 'De meta data zijn opgeslagen');
    }
}

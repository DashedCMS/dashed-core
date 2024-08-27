<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;

class SEOSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Meta data instellingen';

    protected static ?string $navigationGroup = 'Overige';

    protected static ?string $title = 'Meta data instellingen';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["default_meta_data_twitter_site_{$site['id']}"] = Customsetting::get('default_meta_data_twitter_site', $site['id']);
            $formData["default_meta_data_twitter_creator_{$site['id']}"] = Customsetting::get('default_meta_data_twitter_creator', $site['id']);
            $formData["default_meta_data_image_{$site['id']}"] = Customsetting::get('default_meta_data_image', $site['id']);
            $formData['seo_check_models'] = Customsetting::get('seo_check_models', null, false);
            //            $formData["force_trailing_slash"] = Customsetting::get('force_trailing_slash', null, false);
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
                    ->maxLength(255)
                    ->helperText('Bijv: @dashed.dev'),
                TextInput::make("default_meta_data_twitter_creator_{$site['id']}")
                    ->label('Twitter creator')
                    ->maxLength(255)
                    ->helperText('Bijv: @dashed.dev'),
                mediaHelper()->field("default_meta_data_image_{$site['id']}", 'Meta image', false, false, true)
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

        $tabGroups[] =
            Section::make('SEO instellingen voor alle sites')
                ->schema([
                    Toggle::make('seo_check_models')
                        ->label('Check SEO modellen op score')
                        ->helperText('Dit kan het opslaan process vertragen, vraag dit na bij je beheerder.'),
                    //                    Toggle::make("force_trailing_slash")
                    //                        ->label('Forceer trailing slash')
                    //                        ->helperText('Forceer een trailing slash op alle URL\'s, dit kan invloed hebben op de SEO score van je website'),
                ]);

        return $tabGroups;
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('default_meta_data_twitter_site', $this->form->getState()["default_meta_data_twitter_site_{$site['id']}"], $site['id']);
            Customsetting::set('default_meta_data_twitter_creator', $this->form->getState()["default_meta_data_twitter_creator_{$site['id']}"], $site['id']);
            Customsetting::set('default_meta_data_image', $this->form->getState()["default_meta_data_image_{$site['id']}"], $site['id']);
            Customsetting::set('seo_check_models', $this->form->getState()['seo_check_models'], $site['id']);
            //            Customsetting::set('force_trailing_slash', $this->form->getState()["force_trailing_slash"], $site['id']);
        }

        Notification::make()
            ->title('De SEO instellingen zijn opgeslagen')
            ->success()
            ->send();
    }
}

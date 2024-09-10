<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Forms\Components\Actions\Action;

class GeneralSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Algemene instellingen';

    protected static ?string $navigationGroup = 'Overige';

    protected static ?string $title = 'Algemene instellingen';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["site_name_{$site['id']}"] = Customsetting::get('site_name', $site['id']);
            $formData["site_to_email_{$site['id']}"] = Customsetting::get('site_to_email', $site['id']);
            $formData["site_from_email_{$site['id']}"] = Customsetting::get('site_from_email', $site['id']);
            $formData["site_logo_{$site['id']}"] = Customsetting::get('site_logo', $site['id']);
            $formData["site_favicon_{$site['id']}"] = Customsetting::get('site_favicon', $site['id']);
            $formData["company_kvk_{$site['id']}"] = Customsetting::get('company_kvk', $site['id']);
            $formData["company_btw_{$site['id']}"] = Customsetting::get('company_btw', $site['id']);
            $formData["company_phone_number_{$site['id']}"] = Customsetting::get('company_phone_number', $site['id']);
            $formData["company_street_{$site['id']}"] = Customsetting::get('company_street', $site['id']);
            $formData["company_street_number_{$site['id']}"] = Customsetting::get('company_street_number', $site['id']);
            $formData["company_city_{$site['id']}"] = Customsetting::get('company_city', $site['id']);
            $formData["company_postal_code_{$site['id']}"] = Customsetting::get('company_postal_code', $site['id']);
            $formData["company_country_{$site['id']}"] = Customsetting::get('company_country', $site['id']);
            $formData["google_analytics_id_{$site['id']}"] = Customsetting::get('google_analytics_id', $site['id']);
            $formData["google_tagmanager_id_{$site['id']}"] = Customsetting::get('google_tagmanager_id', $site['id']);
            $formData["google_maps_places_key_{$site['id']}"] = Customsetting::get('google_maps_places_key', $site['id']);
            $formData["google_maps_places_id_{$site['id']}"] = Customsetting::get('google_maps_places_id', $site['id']);
            $formData["facebook_pixel_conversion_id_{$site['id']}"] = Customsetting::get('facebook_pixel_conversion_id', $site['id']);
            $formData["facebook_pixel_site_id_{$site['id']}"] = Customsetting::get('facebook_pixel_site_id', $site['id']);
            $formData["webmaster_tag_google_{$site['id']}"] = Customsetting::get('webmaster_tag_google', $site['id']);
            $formData["webmaster_tag_bing_{$site['id']}"] = Customsetting::get('webmaster_tag_bing', $site['id']);
            $formData["webmaster_tag_alexa_{$site['id']}"] = Customsetting::get('webmaster_tag_alexa', $site['id']);
            $formData["webmaster_tag_pinterest_{$site['id']}"] = Customsetting::get('webmaster_tag_pinterest', $site['id']);
            $formData["webmaster_tag_yandex_{$site['id']}"] = Customsetting::get('webmaster_tag_yandex', $site['id']);
            $formData["webmaster_tag_norton_{$site['id']}"] = Customsetting::get('webmaster_tag_norton', $site['id']);
            $formData["extra_scripts_{$site['id']}"] = Customsetting::get('extra_scripts', $site['id']);
            $formData["site_theme_{$site['id']}"] = Customsetting::get('site_theme', $site['id'], 'dashed');
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
                    ->label("Winkelgegevens voor {$site['name']}")
                    ->content('Deze informatie zal de klant gebruiken om contact op te nemen.'),
                TextInput::make("site_name_{$site['id']}")
                    ->label('Site naam')
                    ->required()
                    ->maxLength(255),
                TextInput::make("site_to_email_{$site['id']}")
                    ->label('Contact email')
                    ->required()
                    ->type('email')
                    ->email()
                    ->helperText('We gebruiken dit adres om belangrijke informatie naartoe te sturen.')
                    ->maxLength(60)
                    ->email(),
                TextInput::make("site_from_email_{$site['id']}")
                    ->label('E-mailadres afzender')
                    ->required()
                    ->type('email')
                    ->email()
                    ->helperText('Je klanten zien dit adres als je hun een e-mail stuurt.')
                    ->email()
                    ->maxLength(60),
                TextInput::make("company_kvk_{$site['id']}")
                    ->label('KVK van het bedrijf')
                    ->maxLength(255),
                TextInput::make("company_btw_{$site['id']}")
                    ->label('BTW ID van het bedrijf')
                    ->maxLength(255),
                TextInput::make("company_phone_number_{$site['id']}")
                    ->label('Telefoon')
                    ->maxLength(255),
                TextInput::make("company_street_{$site['id']}")
                    ->label('Straat')
                    ->maxLength(255),
                TextInput::make("company_street_number_{$site['id']}")
                    ->label('Straatnummer')
                    ->maxLength(255),
                TextInput::make("company_city_{$site['id']}")
                    ->label('Stad')
                    ->maxLength(255),
                TextInput::make("company_postal_code_{$site['id']}")
                    ->label('Postcode')
                    ->maxLength(255),
                TextInput::make("company_country_{$site['id']}")
                    ->label('Land/regio')
                    ->maxLength(255),
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

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("Branding voor {$site['name']}")
                    ->content('Upload hier de branding van je website.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                mediaHelper()->field("site_logo_{$site['id']}", 'Logo', false, false, true),
                mediaHelper()->field("site_favicon_{$site['id']}", 'Favicon', false, false, true),
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

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("Externe koppeling voor {$site['name']}")
                    ->content('Stel de UA in om Google Analytics te koppelen, en koppel hier webmaster tools.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("google_analytics_id_{$site['id']}")
                    ->label('Google Analytics ID')
                    ->maxLength(255),
                TextInput::make("google_tagmanager_id_{$site['id']}")
                    ->label('Google Tagmanager ID')
                    ->maxLength(255),
                TextInput::make("google_maps_places_key_{$site['id']}")
                    ->label('Google Maps Places key')
                    ->helperText('Deze key is nodig om de Google Maps Reviews te syncen')
                    ->hintActions([
                        Action::make('retrieveKey')
                            ->label('Verkrijg een key')
                            ->url('https://developers.google.com/maps/documentation/places/web-service/get-api-key')
                            ->openUrlInNewTab(),
                    ])
                    ->maxLength(255),
                TextInput::make("google_maps_places_id_{$site['id']}")
                    ->label('Google Maps Places ID')
                    ->hintActions([
                        Action::make('retrieveId')
                            ->label('Zoek Google Place ID')
                            ->url('https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder')
                            ->openUrlInNewTab(),
                    ])
                    ->maxLength(255),
                TextInput::make("facebook_pixel_conversion_id_{$site['id']}")
                    ->label('Facebook Pixel Conversion ID')
                    ->maxLength(255),
                TextInput::make("facebook_pixel_site_id_{$site['id']}")
                    ->label('Facebook Pixel site ID')
                    ->maxLength(255),
                TextInput::make("webmaster_tag_google_{$site['id']}")
                    ->label('Webmaster tag Google')
                    ->maxLength(255),
                TextInput::make("webmaster_tag_bing_{$site['id']}")
                    ->label('Webmaster tag Bing')
                    ->maxLength(255),
                TextInput::make("webmaster_tag_alexa_{$site['id']}")
                    ->label('Webmaster tag Alexa')
                    ->maxLength(255),
                TextInput::make("webmaster_tag_pinterest_{$site['id']}")
                    ->label('Webmaster tag Pinterest')
                    ->maxLength(255),
                TextInput::make("webmaster_tag_yandex_{$site['id']}")
                    ->label('Webmaster tag Yandex')
                    ->maxLength(255),
                TextInput::make("webmaster_tag_norton_{$site['id']}")
                    ->label('Webmaster tag Norton')
                    ->maxLength(255),
                Textarea::make("extra_scripts_{$site['id']}")
                    ->label('Laad extra scripts in op alle pagina`s')
                    ->rows(10)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Select::make("site_theme_{$site['id']}")
                    ->label('Selecteer het frontend thema voor deze website')
                    ->required()
                    ->options(cms()->builder('themes')),
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

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('site_name', $this->form->getState()["site_name_{$site['id']}"], $site['id']);
            Customsetting::set('site_to_email', $this->form->getState()["site_to_email_{$site['id']}"], $site['id']);
            Customsetting::set('site_from_email', $this->form->getState()["site_from_email_{$site['id']}"], $site['id']);
            Customsetting::set('site_logo', $this->form->getState()["site_logo_{$site['id']}"], $site['id']);
            Customsetting::set('site_favicon', $this->form->getState()["site_favicon_{$site['id']}"], $site['id']);
            Customsetting::set('company_kvk', $this->form->getState()["company_kvk_{$site['id']}"], $site['id']);
            Customsetting::set('company_btw', $this->form->getState()["company_btw_{$site['id']}"], $site['id']);
            Customsetting::set('company_phone_number', $this->form->getState()["company_phone_number_{$site['id']}"], $site['id']);
            Customsetting::set('company_street', $this->form->getState()["company_street_{$site['id']}"], $site['id']);
            Customsetting::set('company_street_number', $this->form->getState()["company_street_number_{$site['id']}"], $site['id']);
            Customsetting::set('company_city', $this->form->getState()["company_city_{$site['id']}"], $site['id']);
            Customsetting::set('company_postal_code', $this->form->getState()["company_postal_code_{$site['id']}"], $site['id']);
            Customsetting::set('company_country', $this->form->getState()["company_country_{$site['id']}"], $site['id']);
            Customsetting::set('google_analytics_id', $this->form->getState()["google_analytics_id_{$site['id']}"], $site['id']);
            Customsetting::set('google_tagmanager_id', $this->form->getState()["google_tagmanager_id_{$site['id']}"], $site['id']);
            Customsetting::set('google_maps_places_key', $this->form->getState()["google_maps_places_key_{$site['id']}"], $site['id']);
            Customsetting::set('google_maps_places_id', $this->form->getState()["google_maps_places_id_{$site['id']}"], $site['id']);
            Customsetting::set('facebook_pixel_conversion_id', $this->form->getState()["facebook_pixel_conversion_id_{$site['id']}"], $site['id']);
            Customsetting::set('facebook_pixel_site_id', $this->form->getState()["facebook_pixel_site_id_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_google', $this->form->getState()["webmaster_tag_google_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_bing', $this->form->getState()["webmaster_tag_bing_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_alexa', $this->form->getState()["webmaster_tag_alexa_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_pinterest', $this->form->getState()["webmaster_tag_pinterest_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_yandex', $this->form->getState()["webmaster_tag_yandex_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_norton', $this->form->getState()["webmaster_tag_norton_{$site['id']}"], $site['id']);
            Customsetting::set('extra_scripts', $this->form->getState()["extra_scripts_{$site['id']}"], $site['id']);
            Customsetting::set('site_theme', $this->form->getState()["site_theme_{$site['id']}"], $site['id']);
        }

        Notification::make()
            ->title('De algemene instellingen zijn opgeslagen')
            ->success()
            ->send();
    }
}

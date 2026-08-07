<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Jobs\SyncGoogleReviews;
use Filament\Schemas\Components\Utilities\Get;
use Dashed\DashedCore\Traits\HasSettingsPermission;

class GeneralSettingsPage extends Page
{
    use HasSettingsPermission;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Algemene instellingen';

    protected static string|UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $title = 'Algemene instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        //        SyncGoogleReviews::dispatch();

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
            $formData["company_bank_number_{$site['id']}"] = Customsetting::get('company_bank_number', $site['id']);
            $formData["google_analytics_id_{$site['id']}"] = Customsetting::get('google_analytics_id', $site['id']);
            $formData["google_tagmanager_id_{$site['id']}"] = Customsetting::get('google_tagmanager_id', $site['id']);
            $formData["google_maps_places_key_{$site['id']}"] = Customsetting::get('google_maps_places_key', $site['id']);
            $formData["google_maps_places_id_{$site['id']}"] = Customsetting::get('google_maps_places_id', $site['id']);
            $formData["facebook_pixel_conversion_id_{$site['id']}"] = Customsetting::get('facebook_pixel_conversion_id', $site['id']);
            $formData["facebook_pixel_site_id_{$site['id']}"] = Customsetting::get('facebook_pixel_site_id', $site['id']);
            $formData["trigger_facebook_events_{$site['id']}"] = Customsetting::get('trigger_facebook_events', $site['id']);
            $formData["trigger_tiktok_events_{$site['id']}"] = Customsetting::get('trigger_tiktok_events', $site['id']);
            $formData["webmaster_tag_google_{$site['id']}"] = Customsetting::get('webmaster_tag_google', $site['id']);
            $formData["webmaster_tag_bing_{$site['id']}"] = Customsetting::get('webmaster_tag_bing', $site['id']);
            $formData["webmaster_tag_alexa_{$site['id']}"] = Customsetting::get('webmaster_tag_alexa', $site['id']);
            $formData["webmaster_tag_pinterest_{$site['id']}"] = Customsetting::get('webmaster_tag_pinterest', $site['id']);
            $formData["webmaster_tag_yandex_{$site['id']}"] = Customsetting::get('webmaster_tag_yandex', $site['id']);
            $formData["webmaster_tag_norton_{$site['id']}"] = Customsetting::get('webmaster_tag_norton', $site['id']);
            $formData["extra_scripts_{$site['id']}"] = Customsetting::get('extra_scripts', $site['id']);
            $formData["extra_body_scripts_{$site['id']}"] = Customsetting::get('extra_body_scripts', $site['id']);
            $formData["admin_bar_enabled_{$site['id']}"] = Customsetting::get('admin_bar_enabled', $site['id'], default: true);
            $formData["cache_profile_{$site['id']}"] = Customsetting::get('cache_profile', $site['id'], 'mixed');
            $formData["cloudflare_zone_id_{$site['id']}"] = Customsetting::get('cloudflare_zone_id', $site['id']);
            $formData["cloudflare_api_token_{$site['id']}"] = Customsetting::get('cloudflare_api_token', $site['id']);
            //            $formData["site_theme_{$site['id']}"] = Customsetting::get('site_theme', $site['id'], 'dashed');
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
                TextEntry::make("Winkelgegevens voor {$site['name']}")
                    ->state('Deze informatie zal de klant gebruiken om contact op te nemen.'),
                TextInput::make("site_name_{$site['id']}")
                    ->label(__('Site naam'))
                    ->required()
                    ->maxLength(255),
                TextInput::make("site_to_email_{$site['id']}")
                    ->label(__('Contact email'))
                    ->required()
                    ->type('email')
                    ->email()
                    ->helperText(__('We gebruiken dit adres om belangrijke informatie naartoe te sturen.'))
                    ->maxLength(60)
                    ->email(),
                TextInput::make("site_from_email_{$site['id']}")
                    ->label(__('E-mailadres afzender'))
                    ->required()
                    ->type('email')
                    ->email()
                    ->helperText(__('Je klanten zien dit adres als je hun een e-mail stuurt.'))
                    ->email()
                    ->maxLength(60),
                TextInput::make("company_kvk_{$site['id']}")
                    ->label(__('KVK van het bedrijf'))
                    ->maxLength(255),
                TextInput::make("company_btw_{$site['id']}")
                    ->label(__('BTW ID van het bedrijf'))
                    ->maxLength(255),
                TextInput::make("company_phone_number_{$site['id']}")
                    ->label(__('Telefoon'))
                    ->maxLength(255),
                TextInput::make("company_bank_number_{$site['id']}")
                    ->label(__('Bank nummer'))
                    ->maxLength(255),
                TextInput::make("company_street_{$site['id']}")
                    ->label(__('Straat'))
                    ->maxLength(255),
                TextInput::make("company_street_number_{$site['id']}")
                    ->label(__('Straatnummer'))
                    ->maxLength(255),
                TextInput::make("company_city_{$site['id']}")
                    ->label(__('Stad'))
                    ->maxLength(255),
                TextInput::make("company_postal_code_{$site['id']}")
                    ->label(__('Postcode'))
                    ->maxLength(255),
                TextInput::make("company_country_{$site['id']}")
                    ->label(__('Land/regio'))
                    ->maxLength(255),
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

        $tabs = [];
        foreach ($sites as $site) {
            $newSchema = [
                TextEntry::make("Branding voor {$site['name']}")
                    ->state('Upload hier de branding van je website.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                mediaHelper()->field("site_logo_{$site['id']}", 'Logo', false, false, true),
                mediaHelper()->field("site_favicon_{$site['id']}", 'Favicon', false, false, true),
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

        $tabs = [];
        foreach ($sites as $site) {
            $newSchema = [
                TextEntry::make("Externe koppeling voor {$site['name']}")
                    ->state('Stel de UA in om Google Analytics te koppelen, en koppel hier webmaster tools.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("google_analytics_id_{$site['id']}")
                    ->label(__('Google Analytics ID'))
                    ->maxLength(255),
                TextInput::make("google_tagmanager_id_{$site['id']}")
                    ->label(__('Google Tagmanager ID'))
                    ->maxLength(255),
                TextInput::make("google_maps_places_key_{$site['id']}")
                    ->label(__('Google Maps Places key'))
                    ->helperText(__('Deze key is nodig om de Google Maps Reviews te syncen'))
                    ->hintActions([
                        Action::make('retrieveKey')
                            ->label(__('Verkrijg een key'))
                            ->url('https://developers.google.com/maps/documentation/places/web-service/get-api-key')
                            ->openUrlInNewTab(),
                    ])
                    ->maxLength(255),
                TextInput::make("google_maps_places_id_{$site['id']}")
                    ->label(__('Google Maps Places ID'))
                    ->hintActions([
                        Action::make('retrieveId')
                            ->label(__('Zoek Google Place ID'))
                            ->url('https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder')
                            ->openUrlInNewTab(),
                    ])
                    ->maxLength(255),
                TextInput::make("facebook_pixel_conversion_id_{$site['id']}")
                    ->label(__('Facebook Pixel Conversion ID'))
                    ->maxLength(255),
                TextInput::make("facebook_pixel_site_id_{$site['id']}")
                    ->label(__('Facebook Pixel site ID'))
                    ->maxLength(255),
                Toggle::make("trigger_facebook_events_{$site['id']}")
                    ->label(__('Trigger Facebook events')),
                Toggle::make("trigger_tiktok_events_{$site['id']}")
                    ->label(__('Trigger TikTok events')),
                TextInput::make("webmaster_tag_google_{$site['id']}")
                    ->label(__('Webmaster tag Google'))
                    ->maxLength(255),
                TextInput::make("webmaster_tag_bing_{$site['id']}")
                    ->label(__('Webmaster tag Bing'))
                    ->maxLength(255),
                TextInput::make("webmaster_tag_alexa_{$site['id']}")
                    ->label(__('Webmaster tag Alexa'))
                    ->maxLength(255),
                TextInput::make("webmaster_tag_pinterest_{$site['id']}")
                    ->label(__('Webmaster tag Pinterest'))
                    ->maxLength(255),
                TextInput::make("webmaster_tag_yandex_{$site['id']}")
                    ->label(__('Webmaster tag Yandex'))
                    ->maxLength(255),
                TextInput::make("webmaster_tag_norton_{$site['id']}")
                    ->label(__('Webmaster tag Norton'))
                    ->maxLength(255),
                Textarea::make("extra_scripts_{$site['id']}")
                    ->label(__('Laad extra scripts in op alle pagina`s'))
                    ->helperText(__('Bovenin de head tag van de website'))
                    ->rows(10)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Textarea::make("extra_body_scripts_{$site['id']}")
                    ->label(__('Laad extra scripts in op alle pagina`s'))
                    ->helperText(__('Bovenin de body tag van de website'))
                    ->rows(10)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Toggle::make("admin_bar_enabled_{$site['id']}")
                    ->label(__('Admin-balk inschakelen'))
                    ->helperText(__('Toon een balk bovenaan de website voor admins met een directe link naar de bewerkpagina van het huidige model.'))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Select::make("cache_profile_{$site['id']}")
                    ->label(__('Cache-profiel'))
                    ->options([
                        'b2c' => __('B2C (agressief cachen)'),
                        'b2b' => __('B2B (per-klant, minimaal cachen)'),
                        'mixed' => __('Gemengd (edge voor anoniem, bypass bij login)'),
                        'off' => __('Uit'),
                    ])
                    ->default('mixed')
                    ->helperText(__('Bepaalt hoe agressief deze site gecachet wordt in latere fases. Nu nog niet actief op response-niveau.'))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("cloudflare_zone_id_{$site['id']}")
                    ->label(__('Cloudflare zone ID'))
                    ->helperText(__('Alleen nodig voor edge (Cloudflare) caching. Te vinden in het Cloudflare-dashboard van de site.'))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("cloudflare_api_token_{$site['id']}")
                    ->label(__('Cloudflare API token'))
                    ->password()
                    ->helperText(__('Alleen nodig voor edge (Cloudflare) caching. Maak een token aan via Cloudflare met Cache Purge-rechten.'))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
//                Select::make("site_theme_{$site['id']}")
//                    ->label('Selecteer het frontend thema voor deze website')
//                    ->required()
//                    ->options(cms()->builder('themes')),
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
            Customsetting::set('company_bank_number', $this->form->getState()["company_bank_number_{$site['id']}"], $site['id']);
            Customsetting::set('google_analytics_id', $this->form->getState()["google_analytics_id_{$site['id']}"], $site['id']);
            Customsetting::set('google_tagmanager_id', $this->form->getState()["google_tagmanager_id_{$site['id']}"], $site['id']);
            Customsetting::set('google_maps_places_key', $this->form->getState()["google_maps_places_key_{$site['id']}"], $site['id']);
            Customsetting::set('google_maps_places_id', $this->form->getState()["google_maps_places_id_{$site['id']}"], $site['id']);
            Customsetting::set('facebook_pixel_conversion_id', $this->form->getState()["facebook_pixel_conversion_id_{$site['id']}"], $site['id']);
            Customsetting::set('facebook_pixel_site_id', $this->form->getState()["facebook_pixel_site_id_{$site['id']}"], $site['id']);
            Customsetting::set('trigger_facebook_events', $this->form->getState()["trigger_facebook_events_{$site['id']}"], $site['id']);
            Customsetting::set('trigger_tiktok_events', $this->form->getState()["trigger_tiktok_events_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_google', $this->form->getState()["webmaster_tag_google_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_bing', $this->form->getState()["webmaster_tag_bing_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_alexa', $this->form->getState()["webmaster_tag_alexa_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_pinterest', $this->form->getState()["webmaster_tag_pinterest_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_yandex', $this->form->getState()["webmaster_tag_yandex_{$site['id']}"], $site['id']);
            Customsetting::set('webmaster_tag_norton', $this->form->getState()["webmaster_tag_norton_{$site['id']}"], $site['id']);
            Customsetting::set('extra_scripts', $this->form->getState()["extra_scripts_{$site['id']}"], $site['id']);
            Customsetting::set('extra_body_scripts', $this->form->getState()["extra_body_scripts_{$site['id']}"], $site['id']);
            Customsetting::set('admin_bar_enabled', $this->form->getState()["admin_bar_enabled_{$site['id']}"], $site['id']);
            Customsetting::set('cache_profile', $this->form->getState()["cache_profile_{$site['id']}"], $site['id']);
            Customsetting::set('cloudflare_zone_id', $this->form->getState()["cloudflare_zone_id_{$site['id']}"], $site['id']);
            Customsetting::set('cloudflare_api_token', $this->form->getState()["cloudflare_api_token_{$site['id']}"], $site['id']);
            //            Customsetting::set('site_theme', $this->form->getState()["site_theme_{$site['id']}"], $site['id']);
        }

        if (Customsetting::get('google_maps_places_key') && Customsetting::get('google_maps_places_id')) {
            SyncGoogleReviews::dispatch();
        }

        Notification::make()
            ->title(__('De algemene instellingen zijn opgeslagen'))
            ->success()
            ->send();
    }
}

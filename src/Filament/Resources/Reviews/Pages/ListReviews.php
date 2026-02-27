<?php

namespace Dashed\DashedCore\Filament\Resources\Reviews\Pages;

use Dashed\DashedCore\Support\GoogleBusinessLocationsService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Filament\Resources\Reviews\ReviewResource;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('editGoogleInfo')
                ->label('Google Reviews instellingen')
                ->icon('heroicon-o-cog-6-tooth')
                ->modalHeading('Google Business Profile instellingen')
                ->modalWidth('2xl')
                ->schema([

                    Placeholder::make('uitleg')
                        ->content(new \Illuminate\Support\HtmlString('
                <div style="font-size:14px; line-height:1.6;">
                    <strong>Stap 1 – Maak een Google Cloud project</strong><br>
                    Ga naar <a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a><br>
                    Maak een nieuw project.<br><br>

                    <strong>Stap 2 – Activeer deze API’s:</strong><br>
                    • Business Profile Performance API<br>
                    • My Business Account Management API<br>
                    • My Business Business Information API<br><br>

                    <strong>Stap 3 – Maak OAuth credentials aan</strong><br>
                    Ga naar APIs & Services → Credentials → Create Credentials → OAuth Client ID.<br>
                    Kies: Web application.<br><br>

                    <strong>Belangrijk:</strong> Voeg bij “Authorized redirect URI” deze URL toe:<br>
                    <code>'.route('google.oauth.callback').'</code><br><br>

                    Daarna krijg je je Client ID en Client Secret.
                </div>
            '))
                        ->columnSpanFull(),
                    TextInput::make('google_oauth_client_id')
                        ->label('OAuth Client ID')
                        ->required()
                        ->default(fn () => Customsetting::get('google_oauth_client_id'))
                        ->columnSpanFull(),

                    TextInput::make('google_oauth_client_secret')
                        ->label('OAuth Client Secret')
                        ->password()
                        ->revealable()
                        ->required()
                        ->default(fn () => Customsetting::get('google_oauth_client_secret'))
                        ->columnSpanFull(),

                    Placeholder::make('refresh_token_uitleg')
                        ->content(new \Illuminate\Support\HtmlString('
                <div style="font-size:14px;">
                    <strong>Refresh Token verkrijgen:</strong><br>
                    1. Ga naar jouw OAuth redirect startpagina (bijv. /admin/google/oauth).<br>
                    2. Log in met het Google account dat eigenaar is van het bedrijf.<br>
                    3. Sta toegang toe.<br>
                    4. De refresh token wordt automatisch opgeslagen.<br><br>

                    Zie je geen refresh token? Verwijder dan eerst de app via:<br>
                    <a href="https://myaccount.google.com/permissions" target="_blank">
                    myaccount.google.com/permissions</a>
                </div>
            '))
                        ->columnSpanFull(),

                    TextInput::make('google_oauth_refresh_token')
                        ->label('OAuth Refresh Token')
                        ->default(fn () => Customsetting::get('google_oauth_refresh_token'))
                        ->columnSpanFull(),

                    Placeholder::make('location_help')
                        ->content(new HtmlString('
                <div style="font-size:14px; line-height:1.6;">
                    <strong>Business Location</strong><br><br>
                    Na koppelen kunnen we automatisch je locaties ophalen.<br>
                    Kies hier de juiste locatie uit de dropdown.<br><br>
                    Zie je niks? Klik dan eerst op <strong>Koppel met Google</strong> en daarna op <strong>Ververs lijst</strong> ✅
                </div>
            '))
                        ->columnSpanFull(),

                    Select::make('google_business_location_name')
                        ->label('Business Location')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            try {
                                return app(GoogleBusinessLocationsService::class)->getLocationOptions();
                            } catch (\Throwable $e) {
                                // Als Google faalt: toon leeg en laat user refreshen / settings checken
                                return [];
                            }
                        })
                        ->default(function () {
                            $siteId = Sites::getActive();
                            return Customsetting::get('google_business_location_name', $siteId);
                        })
                        ->required()
                        ->helperText('Kies de locatie die bij jouw Google Business Profile hoort.')
                        ->hintAction(
                            Action::make('refreshLocations')
                                ->label('Ververs lijst')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function () {
                                    app(GoogleBusinessLocationsService::class)->clearCache();

                                    Notification::make()
                                        ->title('Locaties worden opnieuw opgehaald ✅')
                                        ->success()
                                        ->send();
                                })
                        )
                        ->columnSpanFull(),
                ])
                ->extraModalFooterActions([
                    Action::make('connectGoogle')
                        ->label('Koppel met Google')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('primary')
                        ->url(route('google.oauth.redirect'), shouldOpenInNewTab: true),
                ])
                ->action(function (array $data) {

                    foreach ($data as $key => $value) {
                        Customsetting::set($key, $value);
                    }

                    Notification::make()
                        ->title('Google instellingen opgeslagen ✅')
                        ->success()
                        ->send();
                }),
        ];
    }
}

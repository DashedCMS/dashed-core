<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\View;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Classes\RateLimits;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\CmsIdleTimeout;
use Dashed\DashedCore\Classes\CmsIpAllowlist;
use Dashed\DashedCore\Classes\SecurityAlerts;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class SecuritySettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Beveiliging';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $fill = [
            'cms_allowed_ips' => CmsIpAllowlist::entries(),
            'cms_idle_timeout_minutes' => CmsIdleTimeout::minutes(),
            'security_alerts_enabled' => SecurityAlerts::enabled(),
            'security_alert_emails' => SecurityAlerts::configuredEmails(),
        ];

        foreach (array_keys(RateLimits::LIMITERS) as $name) {
            $fill[RateLimits::setting($name)] = RateLimits::perMinute($name);
        }

        $this->form->fill($fill);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('Toegang tot het CMS op IP-adres'))
                    ->description(__('Laat je dit leeg, dan is het CMS vanaf elk adres bereikbaar. Staat er één of meer adressen in, dan kan er alleen nog vanaf die adressen ingelogd en gewerkt worden; elk ander adres krijgt een foutmelding, ook op de inlogpagina.'))
                    ->schema([
                        Repeater::make('cms_allowed_ips')
                            ->label(__('Toegestane IP-adressen'))
                            ->addActionLabel(__('Adres toevoegen'))
                            ->helperText(__('Geef per adres aan van wie het is. Een reeks mag als 198.51.100.0/24. Je bezoekt het CMS nu vanaf :ip; opslaan kan alleen als dat adres in de lijst past, anders sluit je jezelf buiten.', ['ip' => $this->ownIp()]))
                            ->table([
                                Repeater\TableColumn::make(__('Naam')),
                                Repeater\TableColumn::make(__('IP-adres of reeks')),
                            ])
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('Naam'))
                                    ->placeholder(__('Kantoor, thuis, VPN...')),
                                TextInput::make('ip')
                                    ->label(__('IP-adres of reeks'))
                                    ->placeholder('203.0.113.5'),
                            ])
                            ->hintAction(
                                Action::make('addOwnIp')
                                    ->label(__('Mijn IP toevoegen'))
                                    ->icon('heroicon-o-plus')
                                    ->action(function (Repeater $component) {
                                        $rows = array_values((array) $component->getState());

                                        foreach ($rows as $row) {
                                            if (trim((string) ($row['ip'] ?? '')) === $this->ownIp()) {
                                                return;
                                            }
                                        }

                                        $rows[] = ['name' => '', 'ip' => $this->ownIp()];
                                        $component->state($this->keyRows($rows));
                                    }),
                            ),
                    ]),

                Section::make(__('Automatisch uitloggen'))
                    ->description(__('Een beheerder die het CMS een tijd niet gebruikt wordt bij zijn volgende actie uitgelogd. Een open dashboard dat alleen zichzelf ververst telt niet als gebruik.'))
                    ->schema([
                        TextInput::make('cms_idle_timeout_minutes')
                            ->label(__('Uitloggen na'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->suffix(__('minuten zonder activiteit'))
                            ->helperText(__('0 zet het uit. Standaard 120.')),
                    ]),

                Section::make(__('Beveiligingsmeldingen'))
                    ->description(__('Een e-mail zodra een beheerder inlogt vanaf een IP-adres dat voor dat account nieuw is, en bij een mislukte inlogpoging op een beheerdersaccount (hooguit een per kwartier per account). Alles blijft daarnaast gewoon in het inloglogboek staan.'))
                    ->schema([
                        Toggle::make('security_alerts_enabled')
                            ->label(__('Beveiligingsmeldingen versturen')),
                        TagsInput::make('security_alert_emails')
                            ->label(__('Naar deze e-mailadressen'))
                            ->placeholder(__('Typ een adres en druk op Enter'))
                            ->helperText(__('Leeg: naar alle superadmins.'))
                            ->nestedRecursiveRules(['email']),
                    ]),

                Section::make(__('Verzoeklimieten'))
                    ->description(__('Per IP-adres per minuut, op de website. Wie erboven komt krijgt een 429 en moet even wachten. 0 zet een limiet uit. Achter Cloudflare of een load balancer hoort DASHED_TRUSTED_PROXIES gezet te zijn, anders delen alle bezoekers samen een limiet.'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make(RateLimits::setting('dashed-order-pages'))
                            ->label(__('Bestelpagina\'s en downloads op orderhash'))
                            ->helperText(__('Factuur, pakbon, proforma, restbetaling, retourstatus. Standaard 20.'))
                            ->numeric()->integer()->minValue(0),
                        TextInput::make(RateLimits::setting('dashed-cart'))
                            ->label(__('Winkelwagen'))
                            ->helperText(__('Toevoegen, bijwerken, verwijderen, herstellen. Standaard 60.'))
                            ->numeric()->integer()->minValue(0),
                        TextInput::make(RateLimits::setting('dashed-discount-code'))
                            ->label(__('Kortingscode invoeren'))
                            ->helperText(__('Standaard 10.'))
                            ->numeric()->integer()->minValue(0),
                        TextInput::make(RateLimits::setting('dashed-frontend-auth'))
                            ->label(__('Inloggen en registreren op de website'))
                            ->helperText(__('Per IP en per e-mailadres. Standaard 10.'))
                            ->numeric()->integer()->minValue(0),
                    ]),

                Section::make(__('Alle beveiligingsmaatregelen'))
                    ->description(__('Wat het CMS doet om de beheeromgeving en de webshop te beschermen, en waar elke maatregel ingesteld wordt. Wat er op deze installatie echt aan of uit staat zie je op de Beveiligingscheck.'))
                    ->collapsible()
                    ->schema([
                        View::make('dashed-core::settings.partials.security-measures'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $entries = CmsIpAllowlist::normalize(array_values((array) ($this->form->getState()['cms_allowed_ips'] ?? [])));

        $invalid = CmsIpAllowlist::invalidEntries($entries);

        if ($invalid) {
            Notification::make()
                ->title(__('De lijst bevat een ongeldig adres: :regels', ['regels' => implode(', ', $invalid)]))
                ->danger()
                ->send();

            return;
        }

        // Jezelf buitensluiten is de ene fout die dit scherm niet mag toelaten:
        // wie hem maakt kan daarna niet meer bij dit scherm om hem te herstellen.
        if ($entries && ! \Symfony\Component\HttpFoundation\IpUtils::checkIp((string) $this->ownIp(), array_column($entries, 'ip'))) {
            Notification::make()
                ->title(__('Je eigen adres staat niet in de lijst'))
                ->body(__('Je bezoekt het CMS nu vanaf :ip. Zet dat adres erbij (of een reeks waar het in past), anders sluit je jezelf buiten.', ['ip' => $this->ownIp()]))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        CmsIpAllowlist::save($entries);

        $state = $this->form->getState();
        $siteId = CmsIpAllowlist::siteId();

        Customsetting::set(CmsIdleTimeout::SETTING, max(0, (int) ($state['cms_idle_timeout_minutes'] ?? CmsIdleTimeout::DEFAULT_MINUTES)), $siteId);
        Customsetting::set(SecurityAlerts::SETTING_ENABLED, (bool) ($state['security_alerts_enabled'] ?? true), $siteId);
        Customsetting::set(SecurityAlerts::SETTING_EMAILS, implode("\n", array_values(array_filter(array_map('trim', (array) ($state['security_alert_emails'] ?? []))))), $siteId);

        foreach (array_keys(RateLimits::LIMITERS) as $name) {
            Customsetting::set(RateLimits::setting($name), max(0, (int) ($state[RateLimits::setting($name)] ?? RateLimits::default($name))), $siteId);
        }

        Notification::make()
            ->title(__('De beveiligingsinstellingen zijn opgeslagen'))
            ->success()
            ->send();

        return redirect(static::getUrl());
    }

    /**
     * Filament bewaart repeater-rijen onder een sleutel per rij; bij het zelf
     * zetten van de staat geven we die sleutels mee.
     *
     * @param  array<int, array{name?: string, ip?: string}>  $rows
     * @return array<string, array{name: string, ip: string}>
     */
    protected function keyRows(array $rows): array
    {
        $keyed = [];

        foreach ($rows as $row) {
            $keyed[(string) Str::uuid()] = [
                'name' => (string) ($row['name'] ?? ''),
                'ip' => (string) ($row['ip'] ?? ''),
            ];
        }

        return $keyed;
    }

    protected function ownIp(): string
    {
        return (string) request()->ip();
    }
}

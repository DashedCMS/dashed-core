<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Classes\CmsIpAllowlist;
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
        $this->form->fill([
            'cms_allowed_ips' => CmsIpAllowlist::entries(),
        ]);
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

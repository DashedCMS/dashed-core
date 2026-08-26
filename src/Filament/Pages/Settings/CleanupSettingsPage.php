<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use Throwable;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Retention\Termijn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

/**
 * De bewaartermijnen van elke tabel die zichzelf via het bewaartermijnenregister
 * heeft aangemeld. Het veld staat op de instellingssleutel van de termijn, dus
 * één termijn levert precies één veld op, gegroepeerd per pakket.
 */
class CleanupSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasSettingsPermission;

    public const DEFAULT_ACTIVITY_LOG_DAYS = 90;

    public const DEFAULT_NOTIFICATIONS_READ_DAYS = 14;

    public const DEFAULT_NOTIFICATIONS_DAYS = 60;

    // Zoals elke andere instellingenpagina van dit pakket: bereikbaar via
    // Instellingen, niet ook nog eens los in de zijbalk.
    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?string $title = 'Opschonen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $waarden = [];

        foreach ($this->termijnen() as $termijn) {
            try {
                // waarde() en niet dagen(): niet elke termijn telt dagen, en het
                // formulier vult hier de eenheid van de termijn zelf in.
                $waarden[$this->veldnaam($termijn)] = $termijn->waarde();
            } catch (Throwable) {
                // Een onbruikbare standaard (een lege env-regel bijvoorbeeld)
                // laat waarde() klappen. Juist dit scherm moet dan blijven
                // werken, want hier zet een beheerder er een geldige waarde
                // neer; het veld blijft leeg en is verplicht, dus hij ziet
                // meteen wat er moet gebeuren.
            }
        }

        $this->form->fill($waarden);
    }

    public function form(Schema $schema): Schema
    {
        $secties = [];

        foreach ($this->perPakket() as $pakket => $entries) {
            $velden = [];

            foreach ($entries as $retention) {
                foreach ($retention->termijnen() as $termijn) {
                    // Een termijn in scans heeft een heel andere schaal dan
                    // een termijn in dagen: honderd scans bewaren is veel,
                    // honderd dagen is weinig.
                    $inDagen = $termijn->eenheidNaam() === 'dagen';

                    $veld = TextInput::make($this->veldnaam($termijn))
                        ->label($termijn->labelTekst())
                        ->helperText($termijn->uitlegTekst())
                        ->placeholder((string) $termijn->standaardDagen())
                        ->suffix($termijn->eenheidNaam())
                        ->numeric()
                        ->minValue(1)
                        ->maxValue($inDagen ? 3650 : 100)
                        ->required();

                    if ($termijn->minstensTermijn() !== null) {
                        $andere = $this->vindTermijn($termijn->minstensTermijn());

                        // Wijst de relatie naar een sleutel die niet bestaat (een
                        // half aangemeld pakket), dan gewoon geen regel: de
                        // instellingenpagina mag daar niet op stuklopen.
                        if ($andere !== null) {
                            $veld = $veld->gte($this->veldnaam($andere));
                        }
                    }

                    $velden[] = $veld;
                }
            }

            $secties[] = Section::make($entries[0]->pakketLabel())->schema($velden)->columns(2);
        }

        return $schema->schema($secties)->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        // Een termijn geldt per installatie, niet per site: notifications,
        // activity_log en failed_jobs kennen helemaal geen site. Daarom
        // dezelfde waarde naar elke site, zoals deze pagina dat altijd al deed.
        foreach (Sites::getSites() as $site) {
            foreach ($this->termijnen() as $termijn) {
                $naam = $this->veldnaam($termijn);

                if (array_key_exists($naam, $formData)) {
                    Customsetting::set($termijn->instellingssleutelNaam(), (int) $formData[$naam], $site['id']);
                }
            }
        }

        Notification::make()
            ->title(__('De opschoon instellingen zijn opgeslagen'))
            ->success()
            ->send();
    }

    /** @return array<int, Termijn> */
    protected function termijnen(): array
    {
        return collect(cms()->retentionRegistry()->alles())
            ->flatMap(fn ($retention) => $retention->termijnen())
            ->all();
    }

    /**
     * Zoekt een termijn op zijn eigen Termijn::sleutel() (niet de
     * instellingssleutel), voor de minstens()-relatie tussen twee termijnen.
     */
    protected function vindTermijn(string $sleutel): ?Termijn
    {
        foreach ($this->termijnen() as $termijn) {
            if ($termijn->sleutel() === $sleutel) {
                return $termijn;
            }
        }

        return null;
    }

    /** @return array<string, array<int, \Dashed\DashedCore\Retention\Retention>> */
    protected function perPakket(): array
    {
        return collect(cms()->retentionRegistry()->alles())
            ->groupBy(fn ($retention) => $retention->pakketNaam())
            ->map->all()
            ->all();
    }

    /**
     * De veldnaam is gelijk aan de instellingssleutel, maar een sleutel met een
     * punt erin (print_queue.job_retention_days) zou Filament als geneste
     * staat lezen. Daarom de punt vervangen.
     */
    protected function veldnaam(Termijn $termijn): string
    {
        return str_replace('.', '__', $termijn->instellingssleutelNaam());
    }
}

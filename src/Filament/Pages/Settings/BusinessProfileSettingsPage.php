<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\Seo\BusinessProfile;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

/**
 * Alleen afwijkingen: leeg betekent automatisch (zie BusinessProfile).
 * Onder elk veld staat wat er zonder invullen de deur uitgaat.
 */
class BusinessProfileSettingsPage extends Page implements HasSchemas
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Bedrijfsprofiel';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public const KEYS = ['business_type', 'business_same_as', 'business_price_range', 'business_opening_hours', 'business_geo_lat', 'business_geo_lng'];

    public function mount(): void
    {
        $formData = [];

        foreach (Sites::getSites() as $site) {
            foreach (self::KEYS as $key) {
                $value = Customsetting::get($key, $site['id']);

                if (in_array($key, ['business_same_as', 'business_opening_hours'], true)) {
                    $value = is_string($value) ? (json_decode($value, true) ?: []) : ($value ?: []);
                }

                $formData["{$key}_{$site['id']}"] = $value;
            }
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $tabs = [];

        foreach (Sites::getSites() as $site) {
            $siteId = $site['id'];
            $auto = BusinessProfile::automatic($siteId, app()->getLocale());

            $tabs[] = Tab::make($siteId)
                ->label(ucfirst($site['name']))
                ->schema([
                    Select::make("business_type_{$siteId}")
                        ->label(__('Soort organisatie'))
                        ->options(array_combine(BusinessProfile::TYPES, BusinessProfile::TYPES))
                        ->placeholder(__('Automatisch'))
                        ->helperText(__('Nu automatisch: :waarde', ['waarde' => $auto['type']])),
                    TextInput::make("business_price_range_{$siteId}")
                        ->label(__('Prijsklasse'))
                        ->placeholder('€€')
                        ->maxLength(10)
                        ->helperText(__('Bijvoorbeeld € tot €€€€. Leeg laat de eigenschap weg.')),
                    Repeater::make("business_same_as_{$siteId}")
                        ->label(__('Extra profielen (sameAs)'))
                        ->schema([
                            TextInput::make('url')->label(__('URL'))->url()->required()->startsWith('https://'),
                        ])
                        ->default([])
                        ->helperText(__('Nu automatisch: :waarde', ['waarde' => $auto['sameAs'] ? implode(', ', $auto['sameAs']) : __('geen')]))
                        ->columnSpanFull(),
                    Repeater::make("business_opening_hours_{$siteId}")
                        ->label(__('Openingstijden'))
                        ->schema([
                            Select::make('days')
                                ->label(__('Dagen'))
                                ->multiple()
                                ->options(array_combine(BusinessProfile::DAYS, [__('Maandag'), __('Dinsdag'), __('Woensdag'), __('Donderdag'), __('Vrijdag'), __('Zaterdag'), __('Zondag')]))
                                ->required(),
                            TimePicker::make('opens')->label(__('Opent'))->seconds(false)->required(),
                            TimePicker::make('closes')->label(__('Sluit'))->seconds(false)->required(),
                        ])
                        ->columns(3)
                        ->default([])
                        ->helperText($auto['openingHours']
                            ? __('Nu automatisch uit de chat-openingstijden: :waarde', ['waarde' => collect($auto['openingHours'])->map(fn ($r) => implode(', ', $r['days']) . ' ' . $r['opens'] . '-' . $r['closes'])->implode('; ')])
                            : __('Nu automatisch: geen openingstijden'))
                        ->columnSpanFull(),
                    TextInput::make("business_geo_lat_{$siteId}")
                        ->label(__('Breedtegraad'))
                        ->numeric()
                        ->minValue(-90)
                        ->maxValue(90),
                    TextInput::make("business_geo_lng_{$siteId}")
                        ->label(__('Lengtegraad'))
                        ->numeric()
                        ->minValue(-180)
                        ->maxValue(180),
                ])
                ->columns(['default' => 1, 'lg' => 2]);
        }

        return $schema->schema([Tabs::make('Sites')->tabs($tabs)])->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            foreach (self::KEYS as $key) {
                $value = $state["{$key}_{$site['id']}"] ?? null;

                if ($key === 'business_opening_hours' && is_array($value)) {
                    // TimePicker geeft H:i:s; BusinessProfile verwacht HH:MM.
                    $value = array_map(fn (array $row) => [
                        'days' => array_values((array) ($row['days'] ?? [])),
                        'opens' => substr((string) ($row['opens'] ?? ''), 0, 5),
                        'closes' => substr((string) ($row['closes'] ?? ''), 0, 5),
                    ], array_values($value));
                } elseif (is_array($value)) {
                    $value = array_values($value);
                }

                Customsetting::set($key, $value ?? '', $site['id']);
            }
        }

        Notification::make()
            ->title(__('Het bedrijfsprofiel is opgeslagen'))
            ->success()
            ->send();

        return redirect(self::getUrl());
    }
}

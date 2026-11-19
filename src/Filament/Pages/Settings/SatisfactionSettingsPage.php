<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Satisfaction\Satisfaction;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

/**
 * Per site aan of uit: de tevredenheidsvraag na een chatgesprek en na
 * levering, plus het aantal dagen na levering. Alles staat standaard uit.
 */
class SatisfactionSettingsPage extends Page implements HasSchemas
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Klanttevredenheid';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        foreach (Sites::getSites() as $site) {
            $formData["satisfaction_chat_enabled_{$site['id']}"] = (bool) Customsetting::get('satisfaction_chat_enabled', $site['id'], false);
            $formData["satisfaction_delivery_enabled_{$site['id']}"] = (bool) Customsetting::get('satisfaction_delivery_enabled', $site['id'], false);
            $formData["satisfaction_delivery_days_{$site['id']}"] = Satisfaction::deliveryDays($site['id']);
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $tabs = [];
        foreach (Sites::getSites() as $site) {
            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema([
                    Toggle::make("satisfaction_chat_enabled_{$site['id']}")
                        ->label(__('Vraag na een chatgesprek'))
                        ->helperText(__('De mail met het gesprek dat de bezoeker krijgt als de chat stil valt, vraagt dan om een score van 1 tot 5. Werkt alleen met de livechat.')),
                    Toggle::make("satisfaction_delivery_enabled_{$site['id']}")
                        ->label(__('Vraag na levering'))
                        ->helperText(__('Een mail met vijf klikbare scores, een aantal dagen nadat een bestelling op afgehandeld is gezet. Nooit naar klanten van Bol. Werkt alleen met de webshop.')),
                    TextInput::make("satisfaction_delivery_days_{$site['id']}")
                        ->label(__('Aantal dagen na levering'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->default(Satisfaction::DEFAULT_DELIVERY_DAYS)
                        ->required(),
                ]);
        }

        return $schema->schema([Tabs::make('Sites')->tabs($tabs)])->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            Customsetting::set('satisfaction_chat_enabled', (bool) ($state["satisfaction_chat_enabled_{$site['id']}"] ?? false), $site['id']);
            Customsetting::set('satisfaction_delivery_enabled', (bool) ($state["satisfaction_delivery_enabled_{$site['id']}"] ?? false), $site['id']);
            Customsetting::set('satisfaction_delivery_days', (int) ($state["satisfaction_delivery_days_{$site['id']}"] ?? Satisfaction::DEFAULT_DELIVERY_DAYS), $site['id']);
        }

        Notification::make()
            ->title(__('De instellingen voor klanttevredenheid zijn opgeslagen'))
            ->success()
            ->send();

        return redirect(static::getUrl());
    }
}

<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Classes\Revisions\ContentApproval;

/**
 * Per site: voor welke soorten inhoud de goedkeuringsstroom geldt.
 * Standaard staat hij voor niets aan.
 */
class ContentApprovalSettingsPage extends Page implements HasSchemas
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Goedkeuring';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];

        foreach (Sites::getSites() as $site) {
            $formData[static::fieldFor($site['id'])] = ContentApproval::enabledTypes($site['id']);
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $types = ContentApproval::registeredTypes();
        $tabs = [];

        foreach (Sites::getSites() as $site) {
            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema([
                    TextEntry::make('uitleg_' . md5((string) $site['id']))
                        ->hiddenLabel()
                        ->state(__('Met de goedkeuringsstroom aan slaat een redacteur zonder het recht "Inhoud goedkeuren en publiceren" zijn wijzigingen op als concept. Een beoordelaar keurt het concept goed of af; pas na goedkeuring staat het online. Een nieuw item van zo\'n redacteur staat offline tot het is goedgekeurd.')),
                    CheckboxList::make(static::fieldFor($site['id']))
                        ->label(__('Goedkeuring verplicht voor'))
                        ->options($types)
                        ->helperText($types === [] ? __('Er is nog geen soort inhoud aangemeld voor de goedkeuringsstroom.') : null),
                ]);
        }

        return $schema->schema([
            Tabs::make('Sites')->tabs($tabs),
        ])->statePath('data');
    }

    /**
     * Een site-id kan een punt bevatten (`site.1`), en een punt in een
     * veldnaam is voor Filament een genest pad.
     */
    public static function fieldFor(string $siteId): string
    {
        return 'types_' . preg_replace('/[^A-Za-z0-9_]/', '_', $siteId);
    }

    public function submit()
    {
        $state = $this->form->getState();
        $allowed = array_keys(ContentApproval::registeredTypes());

        foreach (Sites::getSites() as $site) {
            $selected = array_values(array_intersect((array) ($state[static::fieldFor($site['id'])] ?? []), $allowed));
            Customsetting::set(ContentApproval::SETTING, json_encode($selected), $site['id']);
        }

        Notification::make()
            ->title(__('De instellingen voor goedkeuring zijn opgeslagen'))
            ->success()
            ->send();

        return redirect(static::getUrl());
    }
}

<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;
use Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars;

class AnnouncementBarSettingsPage extends Page implements HasSchemas
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Actiebalken';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        foreach (Sites::getSites() as $site) {
            $formData[AnnouncementBars::SETTING . '_' . $site['id']] = AnnouncementBars::enabled($site['id']);
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
                    Toggle::make(AnnouncementBars::SETTING . '_' . $site['id'])
                        ->label(__('Actiebalken tonen op deze site'))
                        ->helperText(__('Standaard uit. De balken zelf beheer je onder Content, Actiebalken.')),
                ]);
        }

        return $schema->schema([Tabs::make('Sites')->tabs($tabs)])->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            Customsetting::set(AnnouncementBars::SETTING, (bool) ($state[AnnouncementBars::SETTING . '_' . $site['id']] ?? false), $site['id']);
            CacheInvalidator::flushSite((string) $site['id']);
        }

        Notification::make()
            ->title(__('De instellingen voor actiebalken zijn opgeslagen'))
            ->success()
            ->send();

        return redirect(static::getUrl());
    }
}

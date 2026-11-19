<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\AbTests\AbTests;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;
use Dashed\DashedCore\Filament\Resources\AbTestResource;

class AbTestSettingsPage extends Page implements HasSchemas
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'A/B-tests';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        foreach (Sites::getSites() as $site) {
            $formData["ab_tests_enabled_{$site['id']}"] = AbTests::enabled($site['id']);
        }

        $this->form->fill($formData);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tests')
                ->label(__('Tests bekijken'))
                ->icon('heroicon-o-beaker')
                ->color('gray')
                ->url(AbTestResource::getUrl()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $tabs = [];
        foreach (Sites::getSites() as $site) {
            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema([
                    Toggle::make("ab_tests_enabled_{$site['id']}")
                        ->label(__('A/B-tests op blokken inschakelen'))
                        ->helperText(__('Toont bij een blok in de pagina-builder de knop A/B-test. Bezoekers krijgen pas een variant te zien en worden pas gemeten na toestemming voor analytische cookies; zonder toestemming zien ze variant A.')),
                ]);
        }

        return $schema
            ->schema([Tabs::make('Sites')->tabs($tabs)])
            ->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            $id = $site['id'];
            $new = (bool) ($state["ab_tests_enabled_{$id}"] ?? false);
            if ($new !== AbTests::enabled($id)) {
                Customsetting::set('ab_tests_enabled', $new, $id);
                // Lopende tests verschijnen of verdwijnen op de pagina's.
                CacheInvalidator::flushSite($id);
            }
        }

        Notification::make()
            ->title(__('De instellingen voor A/B-tests zijn opgeslagen'))
            ->success()
            ->send();

        return redirect(static::getUrl());
    }
}

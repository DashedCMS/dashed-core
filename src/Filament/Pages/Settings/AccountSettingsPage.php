<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedPages\Models\Page as PageModel;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class AccountSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Account';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["account_page_id_{$site['id']}"] = Customsetting::get('account_page_id', $site['id']);
            $formData["login_page_id_{$site['id']}"] = Customsetting::get('login_page_id', $site['id']);
            $formData["forgot_password_page_id_{$site['id']}"] = Customsetting::get('forgot_password_page_id', $site['id']);
            $formData["reset_password_page_id_{$site['id']}"] = Customsetting::get('reset_password_page_id', $site['id']);
            $formData["password_protection_page_id_{$site['id']}"] = Customsetting::get('password_protection_page_id', $site['id']);
            $formData["force_mfa_{$site['id']}"] = Customsetting::get('force_mfa', $site['id']);
            $formData["mfa_app_enabled_{$site['id']}"] = Customsetting::get('mfa_app_enabled', $site['id']);
            $formData["mfa_email_enabled_{$site['id']}"] = Customsetting::get('mfa_email_enabled', $site['id']);
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
                Select::make("account_page_id_{$site['id']}")
                    ->label('Account pagina')
                    ->searchable()
                    ->preload()
                    ->options(PageModel::thisSite($site['id'])->pluck('name', 'id')),
                Select::make("login_page_id_{$site['id']}")
                    ->label('Login pagina')
                    ->searchable()
                    ->preload()
                    ->options(PageModel::thisSite($site['id'])->pluck('name', 'id')),
                Select::make("forgot_password_page_id_{$site['id']}")
                    ->label('Wachtwoord vergeten pagina')
                    ->searchable()
                    ->preload()
                    ->options(PageModel::thisSite($site['id'])->pluck('name', 'id')),
                Select::make("reset_password_page_id_{$site['id']}")
                    ->label('Reset wachtwoord pagina')
                    ->searchable()
                    ->preload()
                    ->options(PageModel::thisSite($site['id'])->pluck('name', 'id')),
                Select::make("password_protection_page_id_{$site['id']}")
                    ->label('Wachtwoord bescherming pagina')
                    ->searchable()
                    ->preload()
                    ->options(PageModel::thisSite($site['id'])->pluck('name', 'id')),
                Toggle::make("force_mfa_{$site['id']}")
                    ->label('Forceer multi factor authenticatie bij het CMS')
                ->helperText('Als je deze optie activeert, activeer dan hieronder minimaal 1 methode.'),
                Toggle::make("mfa_app_enabled_{$site['id']}")
                    ->label('Multi factor authenticatie via een app'),
                Toggle::make("mfa_email_enabled_{$site['id']}")
                    ->label('Multi factor authenticatie via email'),
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
            Customsetting::set('account_page_id', $this->form->getState()["account_page_id_{$site['id']}"], $site['id']);
            Customsetting::set('login_page_id', $this->form->getState()["login_page_id_{$site['id']}"], $site['id']);
            Customsetting::set('forgot_password_page_id', $this->form->getState()["forgot_password_page_id_{$site['id']}"], $site['id']);
            Customsetting::set('reset_password_page_id', $this->form->getState()["reset_password_page_id_{$site['id']}"], $site['id']);
            Customsetting::set('password_protection_page_id', $this->form->getState()["password_protection_page_id_{$site['id']}"], $site['id']);
            Customsetting::set('force_mfa', $this->form->getState()["force_mfa_{$site['id']}"], $site['id']);
            Customsetting::set('mfa_app_enabled', $this->form->getState()["mfa_app_enabled_{$site['id']}"], $site['id']);
            Customsetting::set('mfa_email_enabled', $this->form->getState()["mfa_email_enabled_{$site['id']}"], $site['id']);
        }

        Notification::make()
            ->title('De account instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(AccountSettingsPage::getUrl());
    }
}

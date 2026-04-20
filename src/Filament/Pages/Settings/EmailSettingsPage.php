<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Schemas\Components\Utilities\Get;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class EmailSettingsPage extends Page implements HasSchemas
{
    use HasSettingsPermission;
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'E-mail instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $defaultPrimary = class_exists(\Dashed\DashedTranslations\Models\Translation::class)
            ? \Dashed\DashedTranslations\Models\Translation::get('primary-color-code', 'emails', '#A0131C')
            : '#A0131C';

        $this->form->fill([
            'mail_show_logo' => (bool) Customsetting::get('mail_show_logo', null, 1),
            'mail_logo' => Customsetting::get('mail_logo'),
            'mail_show_site_name' => (bool) Customsetting::get('mail_show_site_name', null, 1),
            'mail_primary_color' => Customsetting::get('mail_primary_color') ?: $defaultPrimary,
            'mail_text_color' => Customsetting::get('mail_text_color', null, '#ffffff'),
            'mail_background_color' => Customsetting::get('mail_background_color', null, '#f3f4f6'),
            'mail_footer_text' => Customsetting::get('mail_footer_text'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Toggle::make('mail_show_logo')
                ->label('Toon logo in e-mails')
                ->helperText('Zet uit als er geen afbeelding in de header getoond moet worden.')
                ->default(true)
                ->live(),
            mediaHelper()->field('mail_logo', 'Logo', false, false, true)
                ->helperText('Laat leeg om het logo uit de algemene instellingen te gebruiken.')
                ->visible(fn (Get $get) => (bool) $get('mail_show_logo')),
            Toggle::make('mail_show_site_name')
                ->label('Toon sitenaam als er geen logo is')
                ->helperText('Als er geen logo getoond wordt, wordt de sitenaam getoond in de header.')
                ->default(true)
                ->visible(fn (Get $get) => ! (bool) $get('mail_show_logo')),
            ColorPicker::make('mail_primary_color')
                ->label('Primaire kleur')
                ->helperText('Wordt gebruikt voor de bovenbalk en knoppen in e-mails.')
                ->required(),
            ColorPicker::make('mail_text_color')
                ->label('Tekstkleur op primaire kleur')
                ->helperText('Kleur van tekst die op de primaire kleur staat (bijv. in de header of op knoppen).')
                ->required(),
            ColorPicker::make('mail_background_color')
                ->label('Achtergrondkleur')
                ->helperText('Achtergrondkleur rond de e-mail container.')
                ->required(),
            TextInput::make('mail_footer_text')
                ->label('Footer tekst')
                ->helperText('Laat leeg om automatisch "© jaar sitenaam" te gebruiken.'),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            Customsetting::set('mail_show_logo', ! empty($formData['mail_show_logo']) ? 1 : 0, $site['id']);
            Customsetting::set('mail_logo', $formData['mail_logo'] ?? '', $site['id']);
            Customsetting::set('mail_show_site_name', ! empty($formData['mail_show_site_name']) ? 1 : 0, $site['id']);
            Customsetting::set('mail_primary_color', $formData['mail_primary_color'], $site['id']);
            Customsetting::set('mail_text_color', $formData['mail_text_color'], $site['id']);
            Customsetting::set('mail_background_color', $formData['mail_background_color'], $site['id']);
            Customsetting::set('mail_footer_text', $formData['mail_footer_text'] ?? '', $site['id']);
        }

        Notification::make()
            ->title('E-mail instellingen opgeslagen')
            ->success()
            ->send();

        redirect(EmailSettingsPage::getUrl());
    }
}

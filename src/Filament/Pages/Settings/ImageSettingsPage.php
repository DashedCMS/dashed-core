<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ImageSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Afbeelding instellingen';

    protected static ?string $navigationGroup = 'Overige';

    protected static ?string $title = 'Afbeelding instellingen';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        //        foreach ($sites as $site) {
        $formData['image_force_lazy_load'] = Customsetting::get('image_force_lazy_load', null, false);
        //            $formData["image_show_sizes"] = Customsetting::get('image_show_sizes', null, false);
        //        }

        $this->form->fill($formData);
    }

    protected function getFormSchema(): array
    {
        //        $sites = Sites::getSites();
        //        $tabGroups = [];
        //
        //        $tabs = [];
        //        foreach ($sites as $site) {
        //            $schema = [
        //                FileUpload::make("default_meta_data_image_{$site['id']}")
        //                    ->label('Meta image')
        //                    ->directory('dashed/metadata')
        //                    ->image()
        //                    ->helperText('Dit is de placeholder meta afbeelding die gebruikt wordt als er geen meta afbeelding is opgegeven.'),
        //            ];
        //
        //            $tabs[] = Tab::make($site['id'])
        //                ->label(ucfirst($site['name']))
        //                ->schema($schema)
        //                ->columns([
        //                    'default' => 1,
        //                    'lg' => 2,
        //                ]);
        //        }
        //        $tabGroups[] = Tabs::make('Sites')
        //            ->tabs($tabs);

        return [
            Toggle::make('image_force_lazy_load')
                ->label('Force lazy load')
                ->helperText('Forceer lazy load voor alle afbeeldingen op de website.')
                ->default(false),
            Toggle::make('image_show_sizes')
                ->label('Toon afbeelding formaten in de image tags')
                ->helperText('Dit kan de website vertragen')
                ->default(false),
        ];
        //        return $tabGroups;
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('image_force_lazy_load', $this->form->getState()['image_force_lazy_load'], $site['id']);
            Customsetting::set('image_show_sizes', $this->form->getState()['image_show_sizes'], $site['id']);
        }

        Notification::make()
            ->title('De afbeelding instellingen zijn opgeslagen')
            ->success()
            ->send();
    }
}

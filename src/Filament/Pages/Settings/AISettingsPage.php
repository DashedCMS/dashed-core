<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Dashed\DashedCore\Classes\OpenAIHelper;
use Dashed\DashedCore\Jobs\CreateAltTextForMediaItem;
use Dashed\DashedCore\Jobs\CreateAltTextsForAllMediaItems;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Forms\Concerns\InteractsWithForms;
use Dashed\DashedPages\Models\Page as PageModel;
use RalphJSmit\Filament\MediaLibrary\Media\Models\MediaLibraryItem;

class AISettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'AI Settings';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $formData["open_ai_api_key"] = Customsetting::get('open_ai_api_key');
        $formData["create_alt_text_for_new_uploaded_images"] = Customsetting::get('create_alt_text_for_new_uploaded_images');

        $this->form->fill($formData);
    }

    protected function getFormSchema(): array
    {
        $schema = [
            Placeholder::make('connected')
                ->label('Open AI is ' . (Customsetting::get('open_ai_connected') ? 'verbonden' : 'niet verbonden')),
            TextInput::make("open_ai_api_key")
                ->label('Open AI API key')
                ->reactive(),
            Toggle::make("create_alt_text_for_new_uploaded_images")
                ->label('Maak automatisch alt tekst voor nieuwe geüploade afbeeldingen')
                ->helperText('Wanneer deze optie is ingeschakeld, zal het systeem automatisch alternatieve tekst genereren voor nieuwe afbeeldingen die worden geüpload. Dit werkt alleen voor Nederlands. Alt teksten zijn niet vertaalbaar.')
                ->visible(fn($get) => $get('open_ai_api_key')),
        ];

        return $schema;
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function submit()
    {
        $sites = Sites::getSites();

        $connected = OpenAIHelper::isConnected($this->form->getState()["open_ai_api_key"]);
        foreach ($sites as $site) {
            Customsetting::set('open_ai_api_key', $this->form->getState()["open_ai_api_key"], $site['id']);
            Customsetting::set('open_ai_connected', $connected, $site['id']);
            Customsetting::set('create_alt_text_for_new_uploaded_images', $this->form->getState()["create_alt_text_for_new_uploaded_images"], $site['id']);
        }

        Notification::make()
            ->title('De AI instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(AISettingsPage::getUrl());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateAltTextForAllImages')
                ->label('Genereer alt teksten voor alle afbeeldingen')
                ->icon('heroicon-o-photo')
                ->color('primary')
                ->form([
                    Toggle::make('overwriteExisting')
                        ->label('Overschrijf bestaande ALT teksten')
                        ->default(false)
                        ->helperText('Indien ingeschakeld, worden bestaande ALT teksten overschreven.'),
                ])
                ->action(function ($data) {
                    $apiKey = Customsetting::get('open_ai_api_key');
                    if (!OpenAIHelper::isConnected($apiKey)) {
                        Notification::make()
                            ->title('Open AI is niet verbonden')
                            ->body('Controleer je API sleutel.')
                            ->danger()
                            ->send();
                        return;
                    }

                    CreateAltTextsForAllMediaItems::dispatch($data['overwriteExisting'] ?? false);

                    Notification::make()
                        ->title('Alt teksten worden gegenereerd')
                        ->success()
                        ->send();
                }),
        ];
    }
}

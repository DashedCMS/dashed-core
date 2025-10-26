<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Classes\OpenAIHelper;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Jobs\CreateAltTextsForAllMediaItems;
use RalphJSmit\Filament\MediaLibrary\Models\MediaLibraryItem;

class AISettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'AI Settings';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $formData["open_ai_api_key"] = Customsetting::get('open_ai_api_key');
        $formData["create_alt_text_for_new_uploaded_images"] = Customsetting::get('create_alt_text_for_new_uploaded_images');

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $newSchema = [
            TextEntry::make('Connectie status')
                ->state('Open AI is ' . (Customsetting::get('open_ai_connected') ? 'verbonden' : 'niet verbonden')),
            TextInput::make('open_ai_api_key')
                ->label('Open AI API sleutel')
                ->reactive(),
            Toggle::make("create_alt_text_for_new_uploaded_images")
                ->label('Maak automatisch alt tekst voor nieuwe geüploade afbeeldingen')
                ->helperText('Wanneer deze optie is ingeschakeld, zal het systeem automatisch alternatieve tekst genereren voor nieuwe afbeeldingen die worden geüpload. Dit werkt alleen voor Nederlands. Alt teksten zijn niet vertaalbaar.')
                ->visible(fn ($get) => $get('open_ai_api_key')),
        ];

        return $schema->schema($newSchema)
            ->statePath('data');
    }

    public function submit()
    {
        $sites = Sites::getSites();

        $connected = OpenAIHelper::isConnected($this->form->getState()["open_ai_api_key"]);
        foreach ($sites as $site) {
            Customsetting::set('open_ai_api_key', $this->form->getState()["open_ai_api_key"], $site['id']);
            Customsetting::set('open_ai_connected', $connected, $site['id']);
            Customsetting::set('create_alt_text_for_new_uploaded_images', $this->form->getState()["create_alt_text_for_new_uploaded_images"] ?? false, $site['id']);
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
                ->schema([
                    TextEntry::make('Genereer alt teksten voor afbeeldingen. Er zijn in totaal ' . MediaLibraryItem::whereHas('media', function ($query) {
                        $query->whereIn('mime_type', [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'image/svg+xml',
                        ]);
                    })->count() . ' afbeeldingen in de media bibliotheek waarvan ' . MediaLibraryItem::whereNull('alt_text')
                            ->whereHas('media', function ($query) {
                                $query->whereIn('mime_type', [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp',
                                    'image/svg+xml',
                                ]);
                            })->count() . ' nog geen alt tekst hebben.')
                        ->helperText('Deze actie genereert automatisch alt teksten voor alle afbeeldingen in de media bibliotheek. Dit kan enige tijd duren, afhankelijk van het aantal afbeeldingen.'),
                    Toggle::make('overwriteExisting')
                        ->label('Overschrijf bestaande ALT teksten')
                        ->default(false)
                        ->helperText('Indien ingeschakeld, worden bestaande ALT teksten overschreven.'),
                ])
                ->action(function ($data) {
                    $apiKey = Customsetting::get('open_ai_api_key');
                    if (! OpenAIHelper::isConnected($apiKey)) {
                        Notification::make()
                            ->title('Open AI is niet verbonden')
                            ->body('Controleer je API sleutel.')
                            ->danger()
                            ->send();

                        return;
                    }
                    //                    foreach(MediaLibraryItem::whereNull('alt_text')->get() as $mediaItem) {
                    //                        OpenAIHelper::getAltTextForImage($apiKey, $mediaItem);
                    //                    }

                    CreateAltTextsForAllMediaItems::dispatch($data['overwriteExisting'] ?? false);

                    Notification::make()
                        ->title('Alt teksten worden gegenereerd')
                        ->success()
                        ->send();
                }),
        ];
    }
}

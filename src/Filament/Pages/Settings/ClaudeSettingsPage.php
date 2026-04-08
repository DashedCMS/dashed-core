<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class ClaudeSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasSettingsPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Claude AI Instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $apiKey = Customsetting::get('claude_api_key');

        // Re-verify connection on page load
        if ($apiKey) {
            $connected = ClaudeHelper::isConnected($apiKey);
            foreach (Sites::getSites() as $site) {
                Customsetting::set('claude_connected', $connected, $site['id']);
            }
        }

        $this->form->fill([
            'claude_api_key' => $apiKey,
            'claude_brand_description' => Customsetting::get('claude_brand_description'),
            'claude_tone_voice' => Customsetting::get('claude_tone_voice'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $connected = (bool) Customsetting::get('claude_connected');
        $usage = ClaudeHelper::getUsage();

        $usageRow = fn (array $period, string $label) =>
            TextEntry::make('usage_' . md5($label))
                ->label($label)
                ->state(
                    number_format($period['total_tokens']) . ' tokens' .
                    '  (' . number_format($period['input_tokens']) . ' in / ' . number_format($period['output_tokens']) . ' out)' .
                    '  •  ' . $period['calls'] . ' aanvragen' .
                    '  •  ~$' . $period['estimated_cost_usd']
                );

        return $schema->schema([
            TextEntry::make('connection_status')
                ->label('Verbindingsstatus')
                ->state('Claude is ' . ($connected ? 'verbonden' : 'niet verbonden'))
                ->badge()
                ->color($connected ? 'success' : 'danger'),

            \Filament\Schemas\Components\Section::make('Verbruik (claude-sonnet-4-6) — beta')
                ->description('Het verbruik en de kostenschatting zijn indicatief en kunnen afwijken van de werkelijke kosten op je Anthropic factuur.')
                ->schema([
                    $usageRow($usage['daily'], 'Vandaag'),
                    $usageRow($usage['weekly'], 'Afgelopen 7 dagen'),
                    $usageRow($usage['monthly'], 'Deze maand'),
                    TextEntry::make('usage_reset')
                        ->label('Maandelijkse reset op')
                        ->state($usage['resets_at']),
                ])
                ->columns(1)
                ->visible($connected),

            TextInput::make('claude_api_key')
                ->label('Claude API sleutel')
                ->password()
                ->revealable()
                ->placeholder('sk-ant-...')
                ->helperText('Je vindt je API sleutel op console.anthropic.com → API Keys.')
                ->reactive(),

            Textarea::make('claude_brand_description')
                ->label('Merkbeschrijving')
                ->helperText('Beschrijf je merk, producten/diensten en doelgroep. Claude gebruikt dit als context bij het schrijven van teksten.')
                ->rows(5)
                ->placeholder("Bijv: Dashed is een Nederlands bureau dat maatwerk Laravel-websites en webshops bouwt voor het MKB. We bouwen op ons eigen Dashed CMS, een open-source pakket bovenop Laravel en Filament. Onze klanten zijn ondernemers en marketing managers die een betrouwbaar, snel en gebruiksvriendelijk CMS willen zonder afhankelijk te zijn van WordPress."),

            Textarea::make('claude_tone_voice')
                ->label('Toon en schrijfstijl')
                ->helperText('Beschrijf hoe Claude moet schrijven. Welke toon, stijl, en wat moet vermeden worden?')
                ->rows(4)
                ->placeholder("Bijv: Schrijf in informeel Nederlands, enthousiast en persoonlijk. Gebruik geen EM-dashes, geen \"eerlijk gezegd\", geen stijve bijvoeglijke naamwoorden. Schrijf actief en direct."),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();
        $apiKey = $formData['claude_api_key'] ?? null;
        $connected = ClaudeHelper::isConnected($apiKey);

        foreach (Sites::getSites() as $site) {
            Customsetting::set('claude_api_key', $apiKey, $site['id']);
            Customsetting::set('claude_connected', $connected, $site['id']);
            Customsetting::set('claude_brand_description', $formData['claude_brand_description'] ?? null, $site['id']);
            Customsetting::set('claude_tone_voice', $formData['claude_tone_voice'] ?? null, $site['id']);
        }

        Notification::make()
            ->title('Claude instellingen opgeslagen')
            ->success()
            ->send();

        redirect(ClaudeSettingsPage::getUrl());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateBrandDescription')
                ->label('Genereer merkbeschrijving automatisch')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => (bool) Customsetting::get('claude_api_key'))
                ->requiresConfirmation()
                ->modalHeading('Merkbeschrijving automatisch genereren')
                ->modalDescription('Claude analyseert de huidige website-inhoud en genereert automatisch een merkbeschrijving en schrijfstijl. De bestaande waarden worden overschreven.')
                ->modalSubmitActionLabel('Genereer')
                ->action(function (): void {
                    $siteName = Customsetting::get('site_name') ?: config('app.name');

                    $samples = ClaudeHelper::collectWebsiteContent();

                    if (! $samples) {
                        Notification::make()
                            ->title('Geen website-inhoud gevonden')
                            ->body('Er zijn nog geen pagina\'s met metadata om van te analyseren.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $prompt = <<<PROMPT
                    Analyseer de onderstaande paginatitels en beschrijvingen van de website "{$siteName}" en schrijf:
                    1. Een korte merkbeschrijving (3-5 zinnen) die beschrijft wat het bedrijf doet, welke producten/diensten ze aanbieden en voor wie.
                    2. Een schrijfstijl omschrijving (2-3 zinnen) op basis van de toon die al gebruikt wordt in de teksten.

                    HUIDIGE PAGINA-INHOUD:
                    {$samples}

                    Retourneer UITSLUITEND geldig JSON in dit formaat (geen markdown):
                    {
                      "brand_description": "...",
                      "tone_voice": "..."
                    }
                    PROMPT;

                    $result = ClaudeHelper::runJsonPrompt($prompt);

                    if (! $result || empty($result['brand_description'])) {
                        Notification::make()
                            ->title('Genereren mislukt')
                            ->body('Claude gaf geen bruikbaar antwoord.')
                            ->danger()
                            ->send();

                        return;
                    }

                    foreach (Sites::getSites() as $site) {
                        Customsetting::set('claude_brand_description', $result['brand_description'], $site['id']);
                        Customsetting::set('claude_tone_voice', $result['tone_voice'] ?? '', $site['id']);
                    }

                    Notification::make()
                        ->title('Merkbeschrijving gegenereerd')
                        ->body('De merkbeschrijving en schrijfstijl zijn automatisch aangemaakt. Controleer ze op de instellingenpagina.')
                        ->success()
                        ->send();

                    redirect(ClaudeSettingsPage::getUrl());
                }),
        ];
    }
}

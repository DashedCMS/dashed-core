<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Throwable;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Notifications\Channels\TelegramChannel;

class NotificationSettingsPage extends Page implements HasSchemas
{
    use HasSettingsPermission;
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Notificatie instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'telegram_enabled' => (bool) Customsetting::get('telegram_enabled'),
            'telegram_bot_token' => Customsetting::get('telegram_bot_token'),
            'telegram_chat_id' => Customsetting::get('telegram_chat_id'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Telegram')
                    ->description('Stuur admin-notificaties (nieuwe orders, formulier inzendingen, lage voorraad, etc.) als beknopte berichten naar een Telegram chat. Mail blijft altijd verstuurd worden - Telegram is een aanvullend kanaal.')
                    ->schema([
                        Toggle::make('telegram_enabled')
                            ->label('Telegram kanaal actief')
                            ->helperText('Zet uit om alle Telegram berichten te pauzeren zonder de instellingen te wissen.'),
                        TextInput::make('telegram_bot_token')
                            ->label('Bot token')
                            ->password()
                            ->revealable()
                            ->helperText('Maak een bot via @BotFather op Telegram en kopieer het token. Format: 123456:ABC-DEF...')
                            ->nullable(),
                        TextInput::make('telegram_chat_id')
                            ->label('Chat ID')
                            ->helperText('Voeg de bot toe aan een (groeps)chat. Gebruik @userinfobot of stuur /start in de groep en haal het chat_id uit de getUpdates response. Voor groepen begint het meestal met -100.')
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            Customsetting::set('telegram_enabled', (bool) ($formData['telegram_enabled'] ?? false), $site['id']);
            Customsetting::set('telegram_bot_token', $formData['telegram_bot_token'] ?? null, $site['id']);
            Customsetting::set('telegram_chat_id', $formData['telegram_chat_id'] ?? null, $site['id']);
        }

        Notification::make()
            ->title('Notificatie instellingen opgeslagen')
            ->success()
            ->send();

        redirect(self::getUrl());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestMessage')
                ->label('Stuur testmelding')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => app(TelegramChannel::class)->isConfigured())
                ->action(function (): void {
                    try {
                        app(TelegramChannel::class)->sendTestMessage();

                        Notification::make()
                            ->title('Testmelding verstuurd')
                            ->body('Check je Telegram chat.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Testmelding mislukt')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

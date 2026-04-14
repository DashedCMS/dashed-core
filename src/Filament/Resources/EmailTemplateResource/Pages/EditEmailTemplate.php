<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;
use Dashed\DashedCore\Mail\EmailRenderer;
use Dashed\DashedCore\Notifications\Channels\TelegramChannel;
use Dashed\DashedCore\Notifications\Contracts\SendsToTelegram;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Test mail sturen')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    TextInput::make('recipient')
                        ->label('Ontvanger')
                        ->email()
                        ->required()
                        ->default(auth()->user()?->email),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $mailableClass = cms()->emailTemplateRegistry()->find($record->mailable_key);

                    $mailable = null;
                    if ($mailableClass && method_exists($mailableClass, 'makeForTest')) {
                        $mailable = $mailableClass::makeForTest();
                    }

                    if ($mailable) {
                        Mail::to($data['recipient'])->send($mailable);
                    } else {
                        $context = $mailableClass ? $mailableClass::sampleData() : [];
                        $renderer = app(EmailRenderer::class);
                        $html = $renderer->render($record, $context);
                        $subject = $renderer->renderSubject($record, $context) ?: $record->name;

                        Mail::html($html, function ($message) use ($data, $subject) {
                            $message->to($data['recipient'])
                                ->subject('[TEST] ' . $subject);
                        });
                    }

                    Notification::make()
                        ->title('Test mail verzonden naar ' . $data['recipient'])
                        ->success()
                        ->send();
                }),

            Action::make('sendTestTelegram')
                ->label('Test Telegram sturen')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(function (): bool {
                    if (! app(TelegramChannel::class)->isConfigured()) {
                        return false;
                    }

                    $record = $this->getRecord();
                    $mailableClass = cms()->emailTemplateRegistry()->find($record->mailable_key);

                    return $mailableClass !== null
                        && is_subclass_of($mailableClass, SendsToTelegram::class)
                        && method_exists($mailableClass, 'makeForTest');
                })
                ->requiresConfirmation()
                ->modalHeading('Test Telegram melding sturen')
                ->modalDescription('Stuurt een sample melding voor deze mail naar de geconfigureerde Telegram chat.')
                ->modalSubmitActionLabel('Versturen')
                ->action(function (): void {
                    $record = $this->getRecord();
                    $mailableClass = cms()->emailTemplateRegistry()->find($record->mailable_key);

                    if (! $mailableClass || ! method_exists($mailableClass, 'makeForTest')) {
                        Notification::make()
                            ->title('Geen testdata beschikbaar')
                            ->body('Deze mailable heeft geen makeForTest() methode.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $mailable = $mailableClass::makeForTest();

                    if (! $mailable instanceof SendsToTelegram) {
                        Notification::make()
                            ->title('Geen Telegram support')
                            ->body('Deze mailable implementeert SendsToTelegram niet.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        app(TelegramChannel::class)->send($mailable->telegramSummary());

                        Notification::make()
                            ->title('Test Telegram verstuurd')
                            ->body('Check je Telegram chat.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Test Telegram mislukt')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

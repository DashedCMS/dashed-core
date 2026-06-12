<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Throwable;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Mail\EmailRenderer;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCore\Notifications\Channels\TelegramChannel;
use Dashed\DashedCore\Notifications\Contracts\SendsToTelegram;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;
use Dashed\DashedCore\Classes\Actions\TranslateEmailTemplateAction;
use Dashed\DashedCore\Classes\Actions\CopyEmailTemplateLocaleAction;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditEmailTemplate extends EditRecord
{
    use Translatable;

    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CopyEmailTemplateLocaleAction::make(),
            TranslateEmailTemplateAction::make(),
            Action::make('resetToDefault')
                ->label('Herstel naar standaard')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Template herstellen naar standaard')
                ->modalDescription('Dit overschrijft het onderwerp en de blokken van de huidige taal met de standaardinhoud van deze mail. Eigen aanpassingen in deze taal gaan verloren.')
                ->modalSubmitActionLabel('Herstellen')
                ->visible(function (): bool {
                    $mailableClass = cms()->emailTemplateRegistry()->find($this->getRecord()->mailable_key);

                    return $mailableClass !== null
                        && (method_exists($mailableClass, 'defaultBlocks') || method_exists($mailableClass, 'defaultSubject'));
                })
                ->action(function (): void {
                    $record = $this->getRecord();
                    $mailableClass = cms()->emailTemplateRegistry()->find($record->mailable_key);

                    if (! $mailableClass) {
                        Notification::make()
                            ->title('Geen standaardinhoud beschikbaar voor deze mail')
                            ->warning()
                            ->send();

                        return;
                    }

                    $locale = $this->activeLocale ?: app()->getLocale();

                    if (method_exists($mailableClass, 'defaultSubject')) {
                        $record->setTranslation('subject', $locale, $mailableClass::defaultSubject());
                    }
                    if (method_exists($mailableClass, 'defaultBlocks')) {
                        $record->setTranslation('blocks', $locale, $mailableClass::defaultBlocks());
                    }
                    $record->save();

                    $this->fillForm();

                    Notification::make()
                        ->title('Template hersteld naar standaard (' . $locale . ')')
                        ->success()
                        ->send();
                }),
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

                    try {
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
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title('Test mail mislukt')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
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

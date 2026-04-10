<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Mail\EmailRenderer;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;

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

                    $context = $mailableClass ? $mailableClass::sampleData() : [];
                    $renderer = app(EmailRenderer::class);
                    $html = $renderer->render($record, $context);
                    $subject = $renderer->renderSubject($record, $context) ?: $record->name;

                    Mail::html($html, function ($message) use ($data, $subject) {
                        $message->to($data['recipient'])
                            ->subject('[TEST] ' . $subject);
                    });

                    Notification::make()
                        ->title('Test mail verzonden naar ' . $data['recipient'])
                        ->success()
                        ->send();
                }),
        ];
    }
}

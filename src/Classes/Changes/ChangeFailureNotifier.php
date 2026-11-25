<?php

namespace Dashed\DashedCore\Classes\Changes;

use Filament\Actions\Action;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Classes\Mails;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Mail\NotificationMail;

/**
 * Meldt de beheerders dat een ingeplande wijziging of bulkbewerking
 * mislukte: een belletje voor admin en superadmin en een mail naar de
 * adressen voor beheerdersmeldingen. Een prijs die na Black Friday niet
 * terugdraait, moet iemand zien.
 */
class ChangeFailureNotifier
{
    public static function notify(string $title, string $body, ?string $url = null): void
    {
        // Het gebruikersmodel van het project, anders landt de melding onder
        // een ander notifiable_type dan waaronder de bel hem zoekt.
        $userModel = config('auth.providers.users.model') ?: User::class;

        foreach ($userModel::query()->panelUsers()->get() as $admin) {
            rescue(function () use ($admin, $title, $body, $url) {
                $notification = Notification::make()->title($title)->body($body)->danger();

                if ($url) {
                    $notification->actions([Action::make('open')->label(__('Bekijken'))->url($url)]);
                }

                $notification->sendToDatabase($admin);
            }, report: false);
        }

        foreach (Mails::getAdminNotificationEmails() as $email) {
            rescue(fn () => Mail::to($email)->send(new NotificationMail($body, $title)), report: false);
        }
    }
}

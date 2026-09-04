<?php

namespace Dashed\DashedCore\Notifications;

use Dashed\DashedCore\Models\Customsetting;
use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Filaments eigen codemail zet geen afzender en valt daardoor terug op
 * mail.from uit de config. Die staat in projecten leeg of op een
 * standaardadres; alle andere CMS-mails gebruiken het afzenderadres en de
 * sitenaam uit de algemene instellingen, en deze mail hoort daar ook bij.
 */
class MfaEmailCodeNotification extends VerifyEmailAuthentication
{
    public function toMail(object $notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);

        $fromEmail = Customsetting::get('site_from_email');

        if ($fromEmail) {
            $mail->from($fromEmail, Customsetting::get('site_name'));
        }

        return $mail;
    }
}

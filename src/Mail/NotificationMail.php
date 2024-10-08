<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;

class NotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(string $notification, string $subject)
    {
        $this->notification = $notification;
        $this->subject = $subject;
    }

    public function build()
    {
        return $this->view(env('SITE_THEME', 'dashed') . '.emails.notification')
            ->from(Customsetting::get('site_from_email'), Customsetting::get('company_name'))
            ->subject($this->subject)
            ->with([
                'notification' => $this->notification,
            ]);
    }
}

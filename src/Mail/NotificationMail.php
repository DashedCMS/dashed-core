<?php

namespace Dashed\DashedCore\Mail;

use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        return $this->view('dashed-core::emails.notification')
            ->from(Customsetting::get('site_from_email'), Customsetting::get('company_name'))
            ->subject($this->subject)
            ->with([
                'notification' => $this->notification,
            ]);
    }
}

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
        $view = view()->exists(config('dashed-core.site_theme') . '.emails.notification') ? config('dashed-core.site_theme') . '.emails.notification' : 'dashed-core::emails.notification';

        return $this->view($view)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->subject($this->subject)
            ->with([
                'notification' => $this->notification,
            ]);
    }
}

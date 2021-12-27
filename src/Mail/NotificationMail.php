<?php

namespace Qubiqx\Qcommerce\Mail;

use Cassandra\Custom;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Qubiqx\QcommerceCore\Models\Customsetting;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(String $notification, string $subject)
    {
        $this->notification = $notification;
        $this->subject = $subject;
    }

    public function build()
    {
        return $this->view('qcommerce-core::emails.notification')
            ->from(Customsetting::get('site_from_email'), Customsetting::get('company_name'))
            ->subject($this->subject)
            ->with([
            'notification' => $this->notification,
        ]);
    }
}

<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Dashed\DashedCore\Filament\Resources\LoginAttemptResource;

/**
 * Een beheerder heeft ingelogd vanaf een adres waarvandaan dat account nog
 * niet eerder is ingelogd. Zie SecurityAlerts.
 */
class CmsLoginFromNewIpMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public string $name,
        public string $ip,
        public string $userAgent,
        public string $at,
    ) {
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.cms-login-from-new-ip')
            ->subject(sprintf('[%s] Login op het CMS vanaf een nieuw IP-adres (%s)', Customsetting::get('site_name', null, 'Dashed'), $this->email))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'siteName' => Customsetting::get('site_name'),
                'attemptsUrl' => LoginAttemptResource::getUrl(),
            ]);
    }
}

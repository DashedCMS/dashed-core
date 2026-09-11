<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Dashed\DashedCore\Filament\Resources\LoginAttemptResource;

/**
 * Een beheerder heeft ingelogd: vanaf een adres waarvandaan dat account nog
 * niet eerder is ingelogd, of (met de schakelaar "bij elke login" aan) bij
 * elke login. Met een ondertekende "dit was ik niet"-link die het account
 * vergrendelt. Zie SecurityAlerts en LockUserAction.
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
        public bool $newIp = true,
        public ?string $lockUrl = null,
        public ?string $allowlistName = null,
    ) {
    }

    public function build(): self
    {
        $subject = $this->newIp
            ? sprintf('[%s] Login op het CMS vanaf een nieuw IP-adres (%s)', Customsetting::get('site_name', null, 'Dashed'), $this->email)
            : sprintf('[%s] Login op het CMS (%s)', Customsetting::get('site_name', null, 'Dashed'), $this->email);

        return $this
            ->view('dashed-core::emails.cms-login-from-new-ip')
            ->subject($subject)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'siteName' => Customsetting::get('site_name'),
                'attemptsUrl' => LoginAttemptResource::getUrl(),
            ]);
    }
}

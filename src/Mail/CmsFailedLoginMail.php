<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Dashed\DashedCore\Filament\Resources\LoginAttemptResource;

/**
 * Een mislukte inlogpoging op een beheerdersaccount. Hooguit een per
 * kwartier per account; zie SecurityAlerts.
 */
class CmsFailedLoginMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public string $ip,
        public string $userAgent,
        public string $at,
        public bool $viaMfa,
        public int $cooldownMinutes,
    ) {
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.cms-failed-login')
            ->subject(sprintf('[%s] Mislukte inlogpoging op het CMS (%s)', Customsetting::get('site_name', null, 'Dashed'), $this->email))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'siteName' => Customsetting::get('site_name'),
                'attemptsUrl' => LoginAttemptResource::getUrl(),
            ]);
    }
}

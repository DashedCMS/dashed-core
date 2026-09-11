<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Er is een wachtwoord-reset aangevraagd voor een paneelaccount. Bewust
 * zonder de resetlink: dit is een signaal aan de beveiligingsontvangers,
 * geen tweede weg naar het wachtwoord. Zie CmsPasswordReset.
 */
class CmsPasswordResetRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public string $ip,
        public string $userAgent,
        public string $at,
        public bool $linkSent,
    ) {
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.cms-password-reset-requested')
            ->subject(sprintf('[%s] Wachtwoord-reset aangevraagd voor een beheerdersaccount (%s)', Customsetting::get('site_name', null, 'Dashed'), $this->email))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with(['siteName' => Customsetting::get('site_name')]);
    }
}

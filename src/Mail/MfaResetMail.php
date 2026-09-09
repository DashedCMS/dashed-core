<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Filament\Facades\Filament;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Naar de gebruiker van wie een superadmin de MFA heeft gereset. Zie
 * ResetMfaAction.
 */
class MfaResetMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $name,
        public string $actor,
        public string $at,
    ) {
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.mfa-reset')
            ->subject(sprintf('[%s] Je tweestapsverificatie is gereset', Customsetting::get('site_name', null, 'Dashed')))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'siteName' => Customsetting::get('site_name'),
                'loginUrl' => rescue(fn () => Filament::getPanel('dashed')->getLoginUrl(), url('/'), false),
            ]);
    }
}

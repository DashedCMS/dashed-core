<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Een bewaakte beheeractie (instelling, prijs, betaalmethode, handmatige
 * betaling) is uitgevoerd. Zie AdminActionMonitor.
 */
class AdminActionAlertMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, string>  $facts
     */
    public function __construct(
        public string $title,
        public array $facts,
    ) {
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.admin-action-alert')
            ->subject(sprintf('[%s] %s', Customsetting::get('site_name', null, 'Dashed'), $this->title))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'siteName' => Customsetting::get('site_name'),
            ]);
    }
}

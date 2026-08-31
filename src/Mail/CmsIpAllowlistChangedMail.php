<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Gaat naar elke superadmin zodra de IP-lijst van het CMS verandert, vanuit
 * het scherm én vanaf de commandoregel. Wie het CMS op adres afsluit of juist
 * weer openzet, hoort dat niet alleen zelf te weten.
 */
class CmsIpAllowlistChangedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $oldEntries
     * @param  array<int, string>  $newEntries
     */
    public function __construct(
        public array $oldEntries,
        public array $newEntries,
        public string $actor,
        public ?string $actorIp,
        public string $changedAt,
    ) {
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.cms-ip-allowlist-changed')
            ->subject(sprintf('[%s] %s', Customsetting::get('site_name', null, 'Dashed'), $this->newEntries ? 'IP-lijst van het CMS gewijzigd' : 'IP-beperking van het CMS opgeheven'))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'oldEntries' => $this->oldEntries,
                'newEntries' => $this->newEntries,
                'actor' => $this->actor,
                'actorIp' => $this->actorIp,
                'changedAt' => $this->changedAt,
                'siteName' => Customsetting::get('site_name'),
                'settingsUrl' => \Dashed\DashedCore\Filament\Pages\Settings\SecuritySettingsPage::getUrl(),
            ]);
    }
}

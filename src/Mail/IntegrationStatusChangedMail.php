<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Dashed\DashedCore\Enums\IntegrationStatus;

/**
 * Verstuurd door dashed:check-integrations-health zodra een koppeling
 * voor het eerst stuk gaat of weer hersteld is. Single shot per state-
 * transition — geen continue spam zolang de status hetzelfde blijft.
 */
class IntegrationStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $slug,
        public string $label,
        public string $oldStatus,
        public string $newStatus,
        public ?string $message,
        public ?string $siteId,
    ) {
    }

    public function build(): self
    {
        $isRecovery = $this->newStatus === IntegrationStatus::Connected->value;

        $subject = $isRecovery
            ? sprintf('[Dashed] Koppeling hersteld: %s', $this->label)
            : sprintf('[Dashed] Probleem met koppeling: %s', $this->label);

        return $this
            ->view('dashed-core::emails.integration-status-changed')
            ->subject($subject)
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'slug' => $this->slug,
                'label' => $this->label,
                'oldStatusLabel' => $this->statusLabel($this->oldStatus),
                'newStatusLabel' => $this->statusLabel($this->newStatus),
                'oldStatusValue' => $this->oldStatus,
                'newStatusValue' => $this->newStatus,
                'message' => $this->message,
                'siteId' => $this->siteId,
                'isRecovery' => $isRecovery,
                'siteName' => Customsetting::get('site_name'),
                'integrationsDashboardUrl' => url('admin/integrations'),
            ]);
    }

    protected function statusLabel(string $value): string
    {
        return IntegrationStatus::tryFrom($value)?->label() ?? $value;
    }
}

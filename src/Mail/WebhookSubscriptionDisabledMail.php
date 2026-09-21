<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\WebhookSubscription;

/**
 * Niet queued: hij wordt al vanuit een job verstuurd. Geen sleutel "message"
 * in de view-data, want die overschrijft Laravel bij het versturen.
 */
class WebhookSubscriptionDisabledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public WebhookSubscription $subscription)
    {
    }

    public function build(): self
    {
        $host = (string) parse_url($this->subscription->url, PHP_URL_HOST);

        return $this
            ->view('dashed-core::emails.webhook-subscription-disabled')
            ->subject(__('[Dashed] Webhook uitgezet: :host', ['host' => $host]))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'webhookUrl' => $this->subscription->url,
                'webhookHost' => $host,
                'reason' => $this->subscription->disabled_reason,
                'ownerLabel' => $this->ownerLabel(),
                'siteName' => Customsetting::get('site_name'),
                'intro' => __('Een webhook is uitgezet omdat de ontvanger te vaak niet antwoordde.'),
                'labelFrom' => __('Van'),
                'labelUrl' => __('Adres'),
                'labelReason' => __('Reden'),
                'outro' => __('Er gaan geen berichten meer naar :host tot iemand de webhook in het CMS weer aanzet. Wat intussen verandert, blijft via de API op te vragen.', ['host' => $host]),
            ]);
    }

    private function ownerLabel(): string
    {
        $owner = $this->subscription->owner;

        if ($owner && method_exists($owner, 'webhookOwnerLabel')) {
            return $owner->webhookOwnerLabel();
        }

        return class_basename($this->subscription->owner_type) . ' #' . $this->subscription->owner_id;
    }
}

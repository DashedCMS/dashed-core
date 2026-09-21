<?php

namespace Dashed\DashedCore\Jobs;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Classes\Mails;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\WebhookDelivery;
use Dashed\DashedCore\Models\WebhookSubscription;
use Dashed\DashedCore\Webhooks\Outgoing\UrlGuard;
use Dashed\DashedCore\Webhooks\Outgoing\WebhookSigner;
use Dashed\DashedCore\Mail\WebhookSubscriptionDisabledMail;

/**
 * Eén poging. Herkansen gebeurt niet door deze job zelf maar door
 * dashed:webhooks:retry, die elke minuut kijkt welke bezorging aan de beurt
 * is: dat overleeft een geleegde wachtrij en loopt op de sync-driver niet in
 * een lus.
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Seconden tot de volgende poging, na poging 1 t/m 5. Poging 6 is de laatste. */
    public const BACKOFF = [60, 300, 1800, 7200, 43200];

    public const DISABLE_AFTER = 20;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('subscription')->find($this->deliveryId);

        if (! $delivery || in_array($delivery->status, [WebhookDelivery::STATUS_SENT, WebhookDelivery::STATUS_ABANDONED], true)) {
            return;
        }

        $subscription = $delivery->subscription;

        if (! $subscription->is_active) {
            $delivery->fill([
                'status' => WebhookDelivery::STATUS_ABANDONED,
                'error' => __('Het abonnement staat uit.'),
                'next_attempt_at' => null,
            ])->save();

            return;
        }

        // Atomair claimen: alleen een bezorging die klaarstaat of waarvan de
        // volgende poging aan de beurt is. Een dubbel klaargezette job, of
        // een kopie die draait terwijl de bezorging nog moet wachten, raakt
        // hier 0 rijen en doet niets.
        $claimed = WebhookDelivery::whereKey($delivery->id)
            ->where(fn ($query) => $query
                ->where('status', WebhookDelivery::STATUS_PENDING)
                ->orWhere(fn ($due) => $due
                    ->where('status', WebhookDelivery::STATUS_FAILED)
                    ->where('next_attempt_at', '<=', now())))
            ->update(['status' => WebhookDelivery::STATUS_SENDING, 'updated_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        $delivery->refresh();

        // Alles vanaf het ophogen van de poging zit in de try: ook een
        // onleesbaar geheim (andere APP_KEY) moet als mislukte poging tellen,
        // anders blijft de bezorging eeuwig hangen zonder ooit op te geven.
        try {
            $delivery->attempt++;

            [$reason, $curl] = UrlGuard::pinned($subscription->url);

            if ($reason !== null) {
                $this->markFailed($delivery, $subscription, null, null, $reason);

                return;
            }

            $timestamp = now()->getTimestamp();
            $body = json_encode([
                'id' => $delivery->id,
                'event' => $delivery->event,
                'created_at' => $delivery->created_at?->toIso8601String(),
                'data' => $delivery->payload,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withOptions(['allow_redirects' => false, 'curl' => $curl])
                ->withHeaders(WebhookSigner::headers($delivery, (string) $subscription->secret, $timestamp, $body))
                ->withBody($body, 'application/json')
                ->post($subscription->url);
        } catch (Throwable $e) {
            $this->markFailed($delivery, $subscription, null, null, $e->getMessage());

            return;
        }

        $excerpt = Str::limit((string) $response->body(), 500, '');

        if ($response->successful()) {
            $delivery->fill([
                'status' => WebhookDelivery::STATUS_SENT,
                'response_code' => $response->status(),
                'response_excerpt' => $excerpt,
                'error' => null,
                'sent_at' => now(),
                'next_attempt_at' => null,
            ])->save();

            $subscription->forceFill(['consecutive_failures' => 0, 'last_success_at' => now()])->save();

            return;
        }

        $this->markFailed($delivery, $subscription, $response->status(), $excerpt, __('HTTP-status :status', ['status' => $response->status()]));
    }

    private function markFailed(WebhookDelivery $delivery, WebhookSubscription $subscription, ?int $code, ?string $excerpt, string $error): void
    {
        $retryIn = self::BACKOFF[$delivery->attempt - 1] ?? null;

        $delivery->fill([
            'status' => $retryIn === null ? WebhookDelivery::STATUS_ABANDONED : WebhookDelivery::STATUS_FAILED,
            'response_code' => $code,
            'response_excerpt' => $excerpt,
            'error' => Str::limit($error, 250, ''),
            'next_attempt_at' => $retryIn === null ? null : now()->addSeconds($retryIn),
        ])->save();

        WebhookSubscription::whereKey($subscription->id)->increment('consecutive_failures');

        $disabled = WebhookSubscription::whereKey($subscription->id)
            ->where('is_active', true)
            ->where('consecutive_failures', '>=', self::DISABLE_AFTER)
            ->update([
                'is_active' => false,
                'disabled_at' => now(),
                'disabled_reason' => __('Uitgezet na :aantal mislukte pogingen op rij.', ['aantal' => self::DISABLE_AFTER]),
            ]);

        // update() geeft alleen 1 terug voor het proces dat hem echt uitzette,
        // dus er gaat maar een mail uit, ook met meerdere workers tegelijk.
        if ($disabled === 1) {
            $recipients = Mails::getAdminNotificationEmails();

            if ($recipients) {
                rescue(fn () => Mail::to($recipients)->send(new WebhookSubscriptionDisabledMail($subscription->fresh())));
            }
        }
    }
}

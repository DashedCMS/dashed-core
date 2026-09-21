<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Jobs\SendWebhookJob;
use Dashed\DashedCore\Models\WebhookDelivery;

class RetryWebhooksCommand extends Command
{
    protected $signature = 'dashed:webhooks:retry';

    protected $description = 'Zet webhook-bezorgingen die aan een nieuwe poging toe zijn opnieuw klaar';

    public function handle(): int
    {
        WebhookDelivery::query()
            ->where(fn ($query) => self::aanDeBeurt($query))
            ->orderBy('id')
            ->limit(1000)
            ->pluck('id')
            ->each(function (int $id): void {
                // Eerst claimen, dan klaarzetten. De claim herhaalt exact de
                // voorwaarden van de selectie: een bezorging die net door een
                // andere run of een job is opgepakt voldoet daar niet meer aan
                // en wordt dus nooit twee keer klaargezet.
                $claimed = WebhookDelivery::whereKey($id)
                    ->where(fn ($query) => self::aanDeBeurt($query))
                    ->update(['status' => WebhookDelivery::STATUS_PENDING, 'updated_at' => now()]);

                if ($claimed === 1) {
                    SendWebhookJob::dispatch($id);
                }
            });

        return self::SUCCESS;
    }

    /**
     * Mislukt en aan de volgende poging toe, of vastgelopen: een job die nooit
     * liep (wachtrij geleegd, worker gecrasht) laat een bezorging op pending
     * staan, een job die halverwege stierf op sending. Na een uur telt die als
     * vastgelopen.
     */
    private static function aanDeBeurt($query)
    {
        return $query
            ->where(fn ($due) => $due
                ->where('status', WebhookDelivery::STATUS_FAILED)
                ->where('next_attempt_at', '<=', now()))
            ->orWhere(fn ($stuck) => $stuck
                ->whereIn('status', [WebhookDelivery::STATUS_PENDING, WebhookDelivery::STATUS_SENDING])
                ->where('updated_at', '<', now()->subHour()));
    }
}

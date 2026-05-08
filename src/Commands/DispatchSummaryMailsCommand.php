<?php

namespace Dashed\DashedCore\Commands;

use Throwable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Mail\SummaryMail;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\SummarySubscription;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface;

/**
 * Verstuurt openstaande admin samenvatting-mails.
 *
 * Wordt elke 15 minuten via de scheduler aangeroepen en pakt alleen
 * subscriptions waarvan next_send_at leeg of verlopen is. Per user
 * worden alle openstaande secties in één mail gebundeld zodat een
 * gebruiker met meerdere subscriptions niet 5 losse mails krijgt.
 */
class DispatchSummaryMailsCommand extends Command
{
    protected $signature = 'dashed:dispatch-summary-mails';

    protected $description = 'Verstuurt openstaande admin samenvatting-mails op basis van dashed__summary_subscriptions';

    public function handle(): int
    {
        $subscriptions = SummarySubscription::query()->due()->get();

        if ($subscriptions->isEmpty()) {
            $this->info('0 subscriptions due.');

            return self::SUCCESS;
        }

        $this->info($subscriptions->count() . ' subscription(s) due.');

        // Resolve contributor-classes uit de builder-registry.
        $registry = $this->resolveContributorRegistry();

        $sent = 0;
        $skipped = 0;

        $grouped = $subscriptions->groupBy('user_id');

        foreach ($grouped as $userId => $userSubs) {
            $user = User::query()->find($userId);
            if (! $user || empty($user->email)) {
                $skipped += $userSubs->count();

                continue;
            }

            $sections = [];
            $processed = [];

            foreach ($userSubs as $subscription) {
                $key = (string) $subscription->contributor_key;
                $class = $registry[$key] ?? null;

                if (! $class || ! class_exists($class) || ! is_subclass_of($class, SummaryContributorInterface::class)) {
                    // Package waarschijnlijk uitgeschakeld, sla over zonder fail.
                    continue;
                }

                $period = $this->periodFor((string) $subscription->frequency);
                if (! $period) {
                    continue;
                }

                try {
                    /** @var class-string<SummaryContributorInterface> $class */
                    $section = $class::contribute($period);
                } catch (Throwable $e) {
                    report($e);
                    $section = null;
                }

                if ($section !== null) {
                    $sections[] = $section;
                }

                $processed[] = [
                    'subscription' => $subscription,
                    'frequency' => (string) $subscription->frequency,
                    'period' => $period,
                ];
            }

            if (count($sections) > 0) {
                // Gebruik de "ruimste" periode als header-label, viel terug
                // op de eerste verwerkte periode als alle even breed zijn.
                $period = $processed[0]['period'] ?? $this->periodFor('daily');

                try {
                    Mail::to($user->email)->send(new SummaryMail($user, $sections, $period));
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                    $skipped += count($processed);

                    continue;
                }
            }

            // Update next_send_at + last_sent_at voor alle verwerkte subs.
            foreach ($processed as $item) {
                /** @var SummarySubscription $subscription */
                $subscription = $item['subscription'];
                $subscription->last_sent_at = now();
                $subscription->next_send_at = $this->nextTickFor((string) $item['frequency']);
                $subscription->save();
            }
        }

        $this->info("Verzonden: {$sent}, overgeslagen: {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Bouwt een SummaryPeriod voor de opgegeven frequency.
     */
    protected function periodFor(string $frequency): ?SummaryPeriod
    {
        return match ($frequency) {
            'daily' => new SummaryPeriod(
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
                'daily',
                'Gisteren',
            ),
            'weekly' => new SummaryPeriod(
                Carbon::now()->subDays(7)->startOfDay(),
                Carbon::now()->subDay()->endOfDay(),
                'weekly',
                'Afgelopen 7 dagen',
            ),
            'monthly' => new SummaryPeriod(
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
                'monthly',
                Carbon::now()->subMonthNoOverflow()->translatedFormat('F Y'),
            ),
            default => null,
        };
    }

    /**
     * Berekent het volgende verstuur-moment op basis van de frequency
     * en het ingestelde dispatch-uur (default 09:00).
     */
    public function nextTickFor(string $frequency): ?Carbon
    {
        $hour = (int) Customsetting::get('summary_dispatch_hour', null, 9);
        if ($hour < 0 || $hour > 23) {
            $hour = 9;
        }

        return match ($frequency) {
            'daily' => Carbon::now()->addDay()->setTime($hour, 0),
            'weekly' => $this->nextMondayAt($hour),
            'monthly' => Carbon::now()->addMonthNoOverflow()->startOfMonth()->setTime($hour, 0),
            default => null,
        };
    }

    /**
     * Eerstvolgende maandag op het opgegeven uur. Als vandaag maandag
     * is en het is voor het dispatch-uur, levert dat vandaag op zodat
     * de scheduler binnen dezelfde week niet alvast doorschuift.
     */
    protected function nextMondayAt(int $hour): Carbon
    {
        $now = Carbon::now();
        if ($now->isMonday() && $now->hour < $hour) {
            return $now->copy()->setTime($hour, 0);
        }

        return $now->copy()->next(Carbon::MONDAY)->setTime($hour, 0);
    }

    /**
     * Lees de geregistreerde contributor-classes uit de builder-registry.
     * Geeft een map van key() -> class-string terug.
     *
     * @return array<string, class-string<SummaryContributorInterface>>
     */
    protected function resolveContributorRegistry(): array
    {
        $registered = function_exists('cms') ? (cms()->builder('summaryContributors', null) ?? []) : [];
        if (! is_array($registered)) {
            return [];
        }

        $map = [];
        foreach ($registered as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }
            if (! is_subclass_of($class, SummaryContributorInterface::class)) {
                continue;
            }

            try {
                /** @var class-string<SummaryContributorInterface> $class */
                $map[$class::key()] = $class;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $map;
    }
}

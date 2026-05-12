<?php

namespace Dashed\DashedCore\Jobs\Concerns;

use Throwable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Mail\JobFailedMail;
use Dashed\DashedCore\Models\JobFailureLog;
use Dashed\DashedCore\Notifications\AdminNotifier;

/**
 * Standard queue-job retry, log context, and failure-notification behavior
 * for the Dashed CMS. Compose into any `ShouldQueue` job:
 *
 *     class MyJob implements ShouldQueue
 *     {
 *         use Queueable, HandlesQueueFailures;
 *
 *         public function handle() { … }
 *     }
 *
 * Override $tries, $timeout, $backoff, $maxExceptions, $notifyOnFailure,
 * $notifyOnEveryAttempt as needed. Override `extraLogContext()` to add
 * job-specific fields (order_id, cart_id, etc.) to the structured log.
 */
trait HandlesQueueFailures
{
    public int $tries = 3;

    public int $timeout = 90;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $maxExceptions = 2;

    public bool $notifyOnFailure = true;

    public bool $notifyOnEveryAttempt = false;

    /**
     * Standard structured log context. Override `extraLogContext()` to add
     * job-specific fields; do NOT override this method.
     *
     * @return array<string, mixed>
     */
    public function logContext(): array
    {
        $base = [
            'site_id' => $this->resolveSiteId(),
            'locale' => app()->getLocale(),
            'user_id' => $this->resolveUserId(),
            'job' => static::class,
            'attempt' => method_exists($this, 'attempts') ? $this->attempts() : null,
            'job_uuid' => isset($this->job) && is_object($this->job) && method_exists($this->job, 'uuid')
                ? $this->job->uuid()
                : null,
        ];

        return array_filter($base, fn ($v) => $v !== null) + $this->extraLogContext();
    }

    /**
     * Hook for sub-classes to add job-specific log fields.
     *
     * @return array<string, mixed>
     */
    public function extraLogContext(): array
    {
        return [];
    }

    /**
     * Terminal-failure handler. Called by the queue worker after all retries
     * have been exhausted (or `maxExceptions` reached). Logs structured
     * context, upserts a dedup row, and notifies admins via the
     * `AdminNotifier` registry — once per (job_class, trace_hash, day) by
     * default. Override `failed()` only if you need extra cleanup; call
     * `$this->reportFailure($e)` from your override to keep the default
     * notification + log behavior.
     */
    public function failed(Throwable $e): void
    {
        $this->reportFailure($e);
    }

    public function reportFailure(Throwable $e): void
    {
        $traceHash = substr(sha1($e->getTraceAsString()), 0, 12);

        $context = $this->logContext() + [
            'exception_class' => $e::class,
            'exception_message' => $e->getMessage(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
            'trace_hash' => $traceHash,
        ];

        $this->writeStructuredLog($context);

        $isFirstOfDay = $this->upsertFailureLog($context, $e);

        if (! $this->notifyOnFailure) {
            return;
        }

        if ($this->notifyOnEveryAttempt || $isFirstOfDay) {
            $this->dispatchAdminAlert($context);
        }

        report($e);
    }

    protected function writeStructuredLog(array $context): void
    {
        try {
            Log::channel('jobs')->error('Job failed: ' . static::class, $context);
        } catch (Throwable) {
            // Channel may not exist on minimal installs — fall back to default.
            Log::error('Job failed: ' . static::class, $context);
        }
    }

    /**
     * Upsert the dedup row for today. Returns true if this is the first
     * recorded failure for (job_class, trace_hash, today).
     */
    protected function upsertFailureLog(array $context, Throwable $e): bool
    {
        try {
            // Use a date-only string and query by exact match. The `date` cast
            // on the column normalises values consistently when read back so
            // both write and read see the same '2026-05-12' shape.
            $today = Carbon::now()->startOfDay();

            $existing = JobFailureLog::query()
                ->where('job_class', static::class)
                ->where('trace_hash', $context['trace_hash'])
                ->whereDate('occurred_on', $today)
                ->first();

            $isFirst = $existing === null;

            if ($isFirst) {
                JobFailureLog::query()->create([
                    'job_class' => static::class,
                    'trace_hash' => $context['trace_hash'],
                    'occurred_on' => $today,
                    'count' => 1,
                    'last_message' => $e->getMessage(),
                    'last_seen_at' => Carbon::now(),
                ]);
            } else {
                JobFailureLog::query()
                    ->where('id', $existing->id)
                    ->update([
                        'count' => $existing->count + 1,
                        'last_message' => $e->getMessage(),
                        'last_seen_at' => Carbon::now(),
                    ]);
            }

            return $isFirst;
        } catch (Throwable $logError) {
            // Don't let the dedup ledger sink the failure path; just notify.
            report($logError);
            return true;
        }
    }

    protected function dispatchAdminAlert(array $context): void
    {
        try {
            AdminNotifier::send(new JobFailedMail($context), null, ['mail', 'telegram']);
        } catch (Throwable $notifyError) {
            // Notification failures must not mask the original job failure.
            report($notifyError);
        }
    }

    protected function resolveSiteId(): ?string
    {
        try {
            $active = Sites::getActive();
            if (is_object($active)) {
                return $active->id ?? null;
            }
            return is_string($active) ? $active : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function resolveUserId(): ?int
    {
        try {
            $id = Auth::id();
            return $id === null ? null : (int) $id;
        } catch (Throwable) {
            return null;
        }
    }
}

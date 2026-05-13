<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Dashed\DashedCore\Notifications\DTOs\TelegramSummary;
use Dashed\DashedCore\Notifications\Contracts\SendsToTelegram;

/**
 * Admin notification for a job that exhausted its retries. Carries the
 * structured log-context the `HandlesQueueFailures` trait collected, plus
 * the exception details.
 *
 * Implements `SendsToTelegram` so the `AdminNotifier` registry can dispatch
 * the same payload via Telegram alongside email.
 */
class JobFailedMail extends Mailable implements SendsToTelegram
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(public array $context)
    {
    }

    public function envelope(): Envelope
    {
        $job = $this->context['job'] ?? 'unknown-job';
        $shortHash = substr((string) ($this->context['trace_hash'] ?? ''), 0, 8);

        return new Envelope(
            subject: "Job failed: {$job} ({$shortHash})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'dashed-core::mail.jobs.job-failed',
            with: ['context' => $this->context],
        );
    }

    public function telegramSummary(): TelegramSummary
    {
        $context = $this->context;

        return new TelegramSummary(
            title: 'Job failed',
            fields: array_filter([
                'Job' => (string) ($context['job'] ?? 'unknown'),
                'Site' => $context['site_id'] ?? null,
                'Attempt' => $context['attempt'] ?? null,
                'Exception' => $context['exception_class'] ?? null,
                'Bericht' => $context['exception_message'] ?? null,
                'Trace' => $context['trace_hash'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            emoji: '🛑',
        );
    }
}

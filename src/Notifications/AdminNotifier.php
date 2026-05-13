<?php

namespace Dashed\DashedCore\Notifications;

use Throwable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Notifications\Channels\TelegramChannel;
use Dashed\DashedCore\Notifications\Contracts\SendsToTelegram;

class AdminNotifier
{
    public function __construct(
        private readonly TelegramChannel $telegram,
    ) {
    }

    /**
     * @param  string|array<int, string>|null  $to
     * @param  array<int, string>|null  $allowedChannels
     */
    public static function send(Mailable $mailable, string|array|null $to = null, ?array $allowedChannels = null): void
    {
        app(self::class)->dispatch($mailable, $to, $allowedChannels);
    }

    /**
     * @param  string|array<int, string>|null  $to
     * @param  array<int, string>|null  $allowedChannels
     */
    public function dispatch(Mailable $mailable, string|array|null $to = null, ?array $allowedChannels = null): void
    {
        if ($this->channelAllowed('mail', $allowedChannels)) {
            $this->sendMail($mailable, $to);
        }

        if ($this->channelAllowed('telegram', $allowedChannels)) {
            $this->sendTelegram($mailable);
        }
    }

    /**
     * @param  array<int, string>|null  $allowedChannels
     */
    private function channelAllowed(string $channel, ?array $allowedChannels): bool
    {
        return $allowedChannels === null || in_array($channel, $allowedChannels, true);
    }

    private function sendMail(Mailable $mailable, string|array|null $to): void
    {
        if ($to !== null) {
            Mail::to($to)->send($mailable);

            return;
        }

        // No explicit recipient - only send if the Mailable defines its own
        // (via `->to()` / `->cc()` / `->bcc()` in its constructor or build).
        // Otherwise skip: Symfony Mailer requires at least one To/Cc/Bcc
        // header and would throw.
        if ($this->mailableHasRecipients($mailable)) {
            Mail::send($mailable);

            return;
        }

        Log::warning('AdminNotifier mail skipped: no recipient configured', [
            'mailable' => $mailable::class,
        ]);
    }

    private function mailableHasRecipients(Mailable $mailable): bool
    {
        return ! empty($mailable->to) || ! empty($mailable->cc) || ! empty($mailable->bcc);
    }

    private function sendTelegram(Mailable $mailable): void
    {
        if (! $mailable instanceof SendsToTelegram) {
            return;
        }

        if (! $this->telegram->isConfigured()) {
            return;
        }

        try {
            $this->telegram->send($mailable->telegramSummary());
        } catch (Throwable $e) {
            Log::warning('AdminNotifier telegram send failed', [
                'mailable' => $mailable::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

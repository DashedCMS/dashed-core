<?php

namespace Dashed\DashedCore\Notifications;

use Dashed\DashedCore\Notifications\Channels\TelegramChannel;
use Dashed\DashedCore\Notifications\Contracts\SendsToTelegram;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminNotifier
{
    public function __construct(
        private readonly TelegramChannel $telegram,
    ) {}

    /**
     * @param  string|array<int, string>|null  $to
     */
    public static function send(Mailable $mailable, string|array|null $to = null): void
    {
        app(self::class)->dispatch($mailable, $to);
    }

    /**
     * @param  string|array<int, string>|null  $to
     */
    public function dispatch(Mailable $mailable, string|array|null $to = null): void
    {
        $this->sendMail($mailable, $to);
        $this->sendTelegram($mailable);
    }

    private function sendMail(Mailable $mailable, string|array|null $to): void
    {
        if ($to !== null) {
            Mail::to($to)->send($mailable);

            return;
        }

        Mail::send($mailable);
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

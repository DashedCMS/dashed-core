<?php

namespace Dashed\DashedCore\Notifications\Channels;

use RuntimeException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Notifications\DTOs\TelegramSummary;

class TelegramChannel
{
    private const API_BASE = 'https://api.telegram.org';
    private const TIMEOUT_SECONDS = 5;

    public function isConfigured(): bool
    {
        return (bool) Customsetting::get('telegram_enabled')
            && (bool) Customsetting::get('telegram_bot_token')
            && (bool) Customsetting::get('telegram_chat_id');
    }

    public function send(TelegramSummary $summary): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $token = (string) Customsetting::get('telegram_bot_token');
        $chatId = (string) Customsetting::get('telegram_chat_id');

        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->retry(2, 250, throw: false)
            ->asJson()
            ->post(self::API_BASE . "/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $summary->toMarkdown(),
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => true,
            ]);

        $this->guardResponse($response);
    }

    public function sendTestMessage(): void
    {
        $this->send(new TelegramSummary(
            title: 'Test notificatie',
            fields: [
                'Status' => 'Verbinding werkt',
                'Site' => (string) Customsetting::get('site_name'),
            ],
            emoji: '✅',
        ));
    }

    private function guardResponse(Response $response): void
    {
        if ($response->successful() && ($response->json('ok') === true)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Telegram API error: HTTP %d - %s',
            $response->status(),
            $response->json('description') ?? $response->body()
        ));
    }
}

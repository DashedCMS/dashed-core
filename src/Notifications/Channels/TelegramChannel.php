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
        $this->sendRaw($summary->toMarkdown());
    }

    /**
     * Stuurt voorbereide MarkdownV2-tekst direct naar Telegram. Splitst
     * automatisch op het 4096-char limiet (op lege regels indien
     * mogelijk) zodat lange gecombineerde berichten niet verloren gaan.
     */
    public function sendRaw(string $markdown): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $token = (string) Customsetting::get('telegram_bot_token');
        $chatId = (string) Customsetting::get('telegram_chat_id');

        foreach ($this->chunkForTelegram($markdown) as $chunk) {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(2, 250, throw: false)
                ->asJson()
                ->post(self::API_BASE . "/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $chunk,
                    'parse_mode' => 'MarkdownV2',
                    'disable_web_page_preview' => true,
                ]);

            $this->guardResponse($response);
        }
    }

    /**
     * @return array<int, string>
     */
    private function chunkForTelegram(string $markdown): array
    {
        $limit = 4000; // 4096 hard limit met buffer voor markdown overhead
        if (mb_strlen($markdown) <= $limit) {
            return [$markdown];
        }

        $chunks = [];
        $remaining = $markdown;

        while (mb_strlen($remaining) > $limit) {
            $slice = mb_substr($remaining, 0, $limit);
            // Probeer op dubbele newline te splitsen, anders enkele newline.
            $split = mb_strrpos($slice, "\n\n");
            if ($split === false || $split < (int) ($limit * 0.5)) {
                $split = mb_strrpos($slice, "\n");
            }
            if ($split === false || $split === 0) {
                $split = $limit;
            }

            $chunks[] = rtrim(mb_substr($remaining, 0, $split));
            $remaining = ltrim(mb_substr($remaining, $split));
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
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

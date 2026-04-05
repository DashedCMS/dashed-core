<?php

namespace Dashed\DashedCore\Classes;

use Exception;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Models\Customsetting;

class ClaudeHelper
{
    public static function isConnected(?string $apiKey = null): bool
    {
        if (! $apiKey) {
            $apiKey = Customsetting::get('claude_api_key');
        }

        if (! $apiKey) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hi'],
                ],
            ]);
        } catch (Exception $e) {
            return false;
        }

        return $response->successful() || $response->status() === 429;
    }

    public static function runPrompt(string $prompt, ?string $apiKey = null, int $maxTokens = 4000): ?string
    {
        if (! $apiKey) {
            $apiKey = Customsetting::get('claude_api_key');
        }

        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (Exception $e) {
            return null;
        }

        if ($response->successful()) {
            return $response->json('content.0.text');
        }

        return null;
    }

    public static function runJsonPrompt(string $prompt, ?string $apiKey = null, int $maxTokens = 4000): ?array
    {
        $text = static::runPrompt($prompt, $apiKey, $maxTokens);

        if (! $text) {
            return null;
        }

        // Strip markdown code blocks if present
        $clean = preg_replace('/^```(?:json)?\n?/', '', trim($text));
        $clean = preg_replace('/\n?```$/', '', $clean);

        return json_decode(trim($clean), true);
    }

    public static function getBrandContext(): string
    {
        $brandDescription = Customsetting::get('claude_brand_description');
        $toneVoice = Customsetting::get('claude_tone_voice');

        $context = '';

        if ($brandDescription) {
            $context .= "MERKBESCHRIJVING:\n{$brandDescription}\n\n";
        }

        if ($toneVoice) {
            $context .= "TON EN STIJL:\n{$toneVoice}\n\n";
        }

        return $context;
    }
}

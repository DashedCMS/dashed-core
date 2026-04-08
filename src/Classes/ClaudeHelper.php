<?php

namespace Dashed\DashedCore\Classes;

use Exception;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Exceptions\ClaudeRateLimitException;

class ClaudeHelper
{
    /**
     * Collect content samples from all registered visitable models (routeModels),
     * including metadata and content block text.
     */
    public static function collectWebsiteContent(): string
    {
        $parts = [];
        $locale = app()->getLocale();

        foreach (cms()->builder('routeModels') as $routeModel) {
            $class = $routeModel['class'];
            $nameField = $routeModel['nameField'] ?? 'name';

            try {
                $records = $class::with(['metadata', 'customBlocks'])->limit(10)->get();
            } catch (\Throwable) {
                continue;
            }

            $lines = [];
            foreach ($records as $record) {
                $name = '';

                try {
                    $name = method_exists($record, 'getTranslation')
                        ? $record->getTranslation($nameField, $locale)
                        : $record->$nameField;
                } catch (\Throwable) {
                }

                $metaTitle = $record->metadata?->getTranslation('title', $locale) ?? '';
                $metaDesc = $record->metadata?->getTranslation('description', $locale) ?? '';

                $blockText = '';

                try {
                    $blocks = $record->customBlocks?->getTranslation('blocks', $locale);
                    if ($blocks) {
                        $blockText = static::extractTextFromBlocks($blocks);
                    }
                } catch (\Throwable) {
                }

                $line = "- {$name}";
                if ($metaTitle) {
                    $line .= " | Meta: {$metaTitle}";
                }
                if ($metaDesc) {
                    $line .= " | {$metaDesc}";
                }
                if ($blockText) {
                    $line .= "\n  Inhoud: " . mb_substr($blockText, 0, 300);
                }

                if ($name || $metaTitle) {
                    $lines[] = $line;
                }
            }

            if ($lines) {
                $parts[] = "{$routeModel['pluralName']}:\n" . implode("\n", $lines);
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Recursively extract plain text from content block data.
     */
    protected static function extractTextFromBlocks(array $blocks): string
    {
        $textFields = ['title', 'subtitle', 'content', 'text', 'description', 'body'];
        $texts = [];

        foreach ($blocks as $block) {
            $data = $block['data'] ?? $block;
            if (! is_array($data)) {
                continue;
            }

            foreach ($textFields as $field) {
                if (isset($data[$field]) && is_string($data[$field]) && trim(strip_tags($data[$field])) !== '') {
                    $texts[] = trim(strip_tags($data[$field]));
                }
            }

            // Recurse into nested blocks
            foreach (['blocks', 'items', 'columns'] as $nested) {
                if (isset($data[$nested]) && is_array($data[$nested])) {
                    $nestedText = static::extractTextFromBlocks($data[$nested]);
                    if ($nestedText) {
                        $texts[] = $nestedText;
                    }
                }
            }
        }

        return implode(' ', $texts);
    }

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

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->successful()) {
            static::trackUsage(
                $response->json('usage.input_tokens', 0),
                $response->json('usage.output_tokens', 0),
            );

            return $response->json('content.0.text');
        }

        $errorMessage = $response->json('error.message') ?? $response->json('error') ?? $response->body();
        $errorType = $response->json('error.type') ?? 'http_' . $response->status();

        if ($response->status() === 429 || $errorType === 'rate_limit_error') {
            throw new ClaudeRateLimitException("[{$errorType}] {$errorMessage}");
        }

        throw new Exception("[{$errorType}] {$errorMessage}");
    }

    public static function runJsonPrompt(string $prompt, ?string $apiKey = null, int $maxTokens = 4000): ?array
    {
        $text = static::runPrompt($prompt, $apiKey, $maxTokens);

        if (! $text) {
            throw new Exception('Claude gaf een leeg antwoord terug.');
        }

        // Strip markdown code blocks if present (handles leading/trailing whitespace)
        $clean = preg_replace('/^\s*```(?:json)?\s*/i', '', trim($text));
        $clean = preg_replace('/\s*```\s*$/', '', $clean);

        // If still no valid JSON, try to extract the first {...} or [...] block
        $decoded = json_decode(trim($clean), true);
        if ($decoded === null) {
            if (preg_match('/(\{[\s\S]*\}|\[[\s\S]*\])/u', $clean, $matches)) {
                $decoded = json_decode($matches[1], true);
            }
        }

        if ($decoded === null) {
            throw new Exception('Claude gaf geen geldig JSON terug. Antwoord: ' . $text);
        }

        return $decoded;
    }

    public static function trackUsage(int $inputTokens, int $outputTokens): void
    {
        // Track per day
        $dayKey = 'claude_usage_day_' . now()->format('Y_m_d');
        $day = Customsetting::get($dayKey, null, []) ?: [];
        $day['input_tokens'] = ($day['input_tokens'] ?? 0) + $inputTokens;
        $day['output_tokens'] = ($day['output_tokens'] ?? 0) + $outputTokens;
        $day['calls'] = ($day['calls'] ?? 0) + 1;
        Customsetting::set($dayKey, $day);

        // Track per month
        $monthKey = 'claude_usage_' . now()->format('Y_m');
        $month = Customsetting::get($monthKey, null, []) ?: [];
        $month['input_tokens'] = ($month['input_tokens'] ?? 0) + $inputTokens;
        $month['output_tokens'] = ($month['output_tokens'] ?? 0) + $outputTokens;
        $month['calls'] = ($month['calls'] ?? 0) + 1;
        Customsetting::set($monthKey, $month);

    }

    public static function getUsage(): array
    {
        // Daily
        $dayKey = 'claude_usage_day_' . now()->format('Y_m_d');
        $dayData = Customsetting::get($dayKey, null, []) ?: [];

        // Weekly (sum last 7 days)
        $weekData = ['input_tokens' => 0, 'output_tokens' => 0, 'calls' => 0];
        for ($i = 0; $i < 7; $i++) {
            $key = 'claude_usage_day_' . now()->subDays($i)->format('Y_m_d');
            $d = Customsetting::get($key, null, []) ?: [];
            $weekData['input_tokens'] += $d['input_tokens'] ?? 0;
            $weekData['output_tokens'] += $d['output_tokens'] ?? 0;
            $weekData['calls'] += $d['calls'] ?? 0;
        }

        // Monthly
        $monthKey = 'claude_usage_' . now()->format('Y_m');
        $monthData = Customsetting::get($monthKey, null, []) ?: [];

        return [
            'daily' => static::formatUsagePeriod($dayData),
            'weekly' => static::formatUsagePeriod($weekData),
            'monthly' => static::formatUsagePeriod($monthData),
            'resets_at' => now()->startOfMonth()->addMonth()->format('d-m-Y'),
        ];
    }

    protected static function formatUsagePeriod(array $data): array
    {
        $input = $data['input_tokens'] ?? 0;
        $output = $data['output_tokens'] ?? 0;

        return [
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' => $input + $output,
            'calls' => $data['calls'] ?? 0,
            // claude-sonnet-4-6: $3/1M input, $15/1M output
            'estimated_cost_usd' => round(($input / 1_000_000 * 3) + ($output / 1_000_000 * 15), 4),
        ];
    }

    public static function getBrandContext(): string
    {
        $brandDescription = Customsetting::get('claude_brand_description');
        $toneVoice = Customsetting::get('claude_tone_voice');

        $context = '';

        if ($brandDescription) {
            $context .= "MERKBESCHRIJVING:\n" . mb_substr($brandDescription, 0, 400) . "\n\n";
        }

        if ($toneVoice) {
            $context .= "TON EN STIJL:\n" . mb_substr($toneVoice, 0, 200) . "\n\n";
        }

        return $context;
    }
}

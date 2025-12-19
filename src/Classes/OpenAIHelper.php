<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\Customsetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RalphJSmit\Filament\MediaLibrary\Models\MediaLibraryItem;

class OpenAIHelper
{
    public static function isConnected(?string $apiKey = null): bool
    {
        if (!$apiKey) {
            return false;
        }

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello!'],
                ],
                'max_tokens' => 1,
            ]);

        return $response->successful() || $response->status() === 429;
    }

    public static function getAltTextForImage(?string $apiKey = null, MediaLibraryItem $mediaLibraryItem): ?string
    {
        if (!$apiKey) {
            $apiKey = Customsetting::get('open_ai_api_key');
        }

        if (!self::isConnected($apiKey)) {
            return null;
        }

        $media = $mediaLibraryItem->media->first();
        if (!$media) {
            return null;
        }

        $imagePath = $media->getPath();

        if (!in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp'])) {
            return null;
        }

        if (Storage::disk('dashed')->exists($imagePath)) {
            $image = Storage::disk('dashed')->get($imagePath);
            $image = base64_encode($image);
            $image = 'data:' . $media->mime_type . ';base64,' . $image;

            try {
                $response = Http::withToken($apiKey)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'Geef een korte, duidelijke alt-tekst voor deze afbeelding. Maximaal 200 karakters. Gebruik geen HTML-tags of speciale tekens. De alt-tekst moet beschrijvend zijn en de inhoud van de afbeelding samenvatten.',
                                    ],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => $image,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'max_tokens' => 100,
                    ]);
            } catch (Exception $exception) {
                return null;
            }

            if ($response->successful()) {
                $response = $response->json();
                $altText = $response['choices'][0]['message']['content'] ?? '';
                $mediaLibraryItem->alt_text = str($altText)->trim()->limit(200, '')->toString();
                $mediaLibraryItem->save();
            }
        }

        return null;
    }

    public static function runPrompt(?string $apiKey = null, string $prompt = ''): ?string
    {
        if (!$apiKey) {
            $apiKey = Customsetting::get('open_ai_api_key');
        }

        if (!self::isConnected($apiKey)) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 10000,
                ]);
        } catch (Exception $exception) {
            return null;
        }

        if ($response->successful()) {
            $response = $response->json();
            $response = $response['choices'][0]['message']['content'] ?? '';
        }

        return null;
    }
}

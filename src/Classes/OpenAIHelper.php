<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Jobs\CreateAltTextForMediaItem;
use Dashed\DashedPages\Models\Page;
use Exception;
use Illuminate\Support\Facades\Auth;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use RalphJSmit\Filament\MediaLibrary\Media\Models\MediaLibraryItem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OpenAIHelper
{
    public static function isConnected(string $apiKey): bool
    {
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

    public static function getAltTextForImage(string $apiKey, MediaLibraryItem $mediaLibraryItem): ?string
    {
        if (!self::isConnected($apiKey)) {
            return null;
        }

        $media = $mediaLibraryItem->media->first();
        if(!$media){
            return null;
        }

        $imagePath = $media->getPath();

        $image = Storage::disk('dashed')->get($imagePath);
        $image = base64_encode($image);
        $image = 'data:' . $media->mime_type . ';base64,' . $image;

        try{
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
                                        'url' => $image
                                    ]
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 100,
                ]);
        }catch (Exception $exception){
            return null;
        }

        if($response->status() === 429) {
            CreateAltTextForMediaItem::dispatch($mediaLibraryItem)->delay(now()->addSeconds(30));
        }else if ($response->successful()) {
            $response = $response->json();
            $altText = $response['choices'][0]['message']['content'] ?? '';
            $mediaLibraryItem->alt_text = str($altText)->trim()->limit(200, '')->toString();
            $mediaLibraryItem->save();
        }

        return null;
    }
}

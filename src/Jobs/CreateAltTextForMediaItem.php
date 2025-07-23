<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Classes\OpenAIHelper;
use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use OpenAI;

class CreateAltTextForMediaItem implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 1200;

    public $uniqueFor = 1200;
    public $mediaItem;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'create-alt-text-for-media-item-' . $this->mediaItem->id;
    }

    /**
     * Create a new job instance.
     */
    public function __construct($mediaItem)
    {
        $this->mediaItem = $mediaItem;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiKey = Customsetting::get('open_ai_api_key');
        if (!OpenAIHelper::isConnected($apiKey)) {
            return;
        }

        OpenAIHelper::getAltTextForImage($apiKey, $this->mediaItem);

        $this->mediaItem->refresh();
        if (!$this->mediaItem->alt_text) {
            CreateAltTextForMediaItem::dispatch($this->mediaItem)->delay(now()->addMinutes(5));
        }
    }
}

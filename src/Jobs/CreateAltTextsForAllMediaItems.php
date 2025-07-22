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
use RalphJSmit\Filament\MediaLibrary\FilamentMediaLibrary;
use RalphJSmit\Filament\MediaLibrary\Media\Models\MediaLibraryItem;

class CreateAltTextsForAllMediaItems implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 1200;

    public $uniqueFor = 1200;
    public bool $overwriteExisting = false;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'create-alt-texts-for-all-media-items';
    }

    /**
     * Create a new job instance.
     */
    public function __construct(bool $overwriteExisting = false)
    {
        $this->overwriteExisting = $overwriteExisting;
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

        if ($this->overwriteExisting) {
            MediaLibraryItem::query()->update(['alt_text' => null]);
        }

        foreach(MediaLibraryItem::whereNull('alt_text')->get() as $mediaItem) {
            CreateAltTextForMediaItem::dispatch($mediaItem);
        }
    }
}

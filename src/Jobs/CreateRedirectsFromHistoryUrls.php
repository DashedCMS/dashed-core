<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Models\Redirect;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateRedirectsFromHistoryUrls implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 12000;

    public string $siteId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach (UrlHistory::whereNotNull('previous_url')->where('site_id', $this->siteId)->get() as $urlHistory) {
            Redirect::handleSlugChange($urlHistory->previous_url, $urlHistory->url);
        }
    }
}

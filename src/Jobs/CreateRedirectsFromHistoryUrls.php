<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Models\Redirect;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateRedirectsFromHistoryUrls implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

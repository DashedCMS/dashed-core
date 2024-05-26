<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Classes\Sites;
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
    public int $batchNumber;


    /**
     * Create a new job instance.
     */
    public function __construct(string $siteId, int $batchNumber)
    {
        $this->siteId = $siteId;
        $this->batchNumber = $batchNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $previousBatch = UrlHistory::where('batch', '<', $this->batchNumber)->where('site_id', $this->siteId)->orderBy('batch', 'desc')->exists();
        if ($previousBatch) {
            foreach (UrlHistory::where('batch', $this->batchNumber)->where('site_id', $this->siteId)->get() as $urlHistory) {
                $previousHistoryUrl = UrlHistory::where('batch', '<', $this->batchNumber)
                    ->where('site_id', $this->siteId)
                    ->where('method', $urlHistory->method)
                    ->where('model_type', $urlHistory->model_type)
                    ->where('model_id', $urlHistory->model_id)
                    ->orderBy('batch', 'desc')
                    ->first();
                if ($previousHistoryUrl) {
                    Redirect::handleSlugChange($previousHistoryUrl->url, $urlHistory->url);
                }
            }
        }
    }
}

<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunUrlHistoryCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batchNumber = UrlHistory::orderBy('batch', 'desc')->first()?->batch + 1;
        foreach (cms()->builder('routeModels') as $routeModel) {
            foreach ($routeModel['class']::publicShowable()->get() as $model) {
                foreach (Locales::getLocales() as $locale) {
                    if (in_array($locale['id'], Sites::get()['locales'])) {
                        Locales::setLocale($locale['id']);
                        $model->urlHistory()->create([
                            'batch' => $batchNumber,
                            'url' => $model->getUrl(),
                            'method' => 'getUrl',
                            'site_id' => Sites::getActive(),
                            'locale' => $locale['id'],
                        ]);
                    }
                }
            }
        }

        CreateRedirectsFromHistoryUrls::dispatch(Sites::getActive(), $batchNumber);
    }
}

<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunUrlHistoryCheck implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    public $uniqueFor = 1200;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'url-history-check';
    }

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
        Customsetting::set('last_history_check', now());

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

        UrlHistory::where('batch', '<', $batchNumber - 50)->delete();
        CreateRedirectsFromHistoryUrls::dispatch(Sites::getActive(), $batchNumber);
    }
}

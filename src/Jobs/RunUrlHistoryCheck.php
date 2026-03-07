<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class RunUrlHistoryCheck implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1200;
    public int $uniqueFor = 1200;

    public function uniqueId(): string
    {
        return 'url-history-check';
    }

    public function handle(): void
    {
        Customsetting::set('last_history_check', now());

        $site = Sites::get();
        $siteId = Sites::getActive();
        $allowedLocales = $site['locales'] ?? [];

        foreach (cms()->builder('routeModels') as $routeModel) {
            $modelClass = $routeModel['class'];

            $modelClass::publicShowable()
                ->chunk(200, function ($models) use ($allowedLocales, $siteId) {
                    foreach ($models as $model) {
                        foreach (Locales::getLocales() as $locale) {
                            $localeId = $locale['id'];

                            if (! in_array($localeId, $allowedLocales, true)) {
                                continue;
                            }

                            Locales::setLocale($localeId);

                            $newUrl = $model->url;

                            $urlHistory = $model->urlHistory()->firstOrNew([
                                'method' => 'getUrl',
                                'site_id' => $siteId,
                                'locale' => $localeId,
                            ]);

                            if ($urlHistory->exists && $urlHistory->url !== $newUrl) {
                                $urlHistory->previous_url = $urlHistory->url;
                            }

                            $urlHistory->url = $newUrl;
                            $urlHistory->save();
                        }
                    }
                });
        }

        CreateRedirectsFromHistoryUrls::dispatch($siteId);
    }
}

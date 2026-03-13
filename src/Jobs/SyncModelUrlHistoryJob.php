<?php

namespace Dashed\DashedCore\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SyncModelUrlHistoryJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;
    public int $uniqueFor = 300;

    public function __construct(
        public string $modelClass,
        public int|string $modelId,
    ) {
    }

    public function uniqueId(): string
    {
        return 'sync-model-url-history:' . $this->modelClass . ':' . $this->modelId;
    }

    public function handle(): void
    {
        if (! class_exists($this->modelClass)) {
            return;
        }

        $model = $this->modelClass::query()->find($this->modelId);

        if (! $model) {
            return;
        }

        if (! method_exists($model, 'urlHistories')) {
            return;
        }

        $site = Sites::get();
        $siteId = Sites::getActive();
        $allowedLocales = $site['locales'] ?? [];

        foreach (Locales::getLocales() as $locale) {
            $localeId = $locale['id'];

            if (! in_array($localeId, $allowedLocales, true)) {
                continue;
            }

            Locales::setLocale($localeId);

            $newUrl = $model->getUrl($localeId);

            $urlHistory = $model->urlHistories()->firstOrNew([
                'method' => 'getUrl',
                'site_id' => $siteId,
                'locale' => $localeId,
            ]);

            $oldUrl = $urlHistory->exists ? $urlHistory->url : null;

            if ($oldUrl === $newUrl) {
                continue;
            }

            if ($oldUrl) {
                $urlHistory->previous_url = $oldUrl;
            }

            $urlHistory->url = $newUrl;
            $urlHistory->save();

            if ($oldUrl && $oldUrl !== $newUrl) {
                CreateRedirectFromHistoryUrlJob::dispatch($urlHistory->id)->afterCommit();
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}

<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Models\Redirect;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateRedirectFromHistoryUrlJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    public function __construct(public int $urlHistoryId)
    {
    }

    public function handle(): void
    {
        $urlHistory = UrlHistory::find($this->urlHistoryId);

        if (! $urlHistory) {
            return;
        }

        if (! $urlHistory->previous_url || $urlHistory->previous_url === $urlHistory->url) {
            return;
        }

        Redirect::handleSlugChange($urlHistory->previous_url, $urlHistory->url);
    }
}

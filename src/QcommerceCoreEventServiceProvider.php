<?php

namespace Qubiqx\QcommerceCore;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Qubiqx\QcommerceCore\Models\Page;
use Qubiqx\QcommerceCore\Observers\PageObserver;

class QcommerceCoreEventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Page::observe(PageObserver::class);
    }
}

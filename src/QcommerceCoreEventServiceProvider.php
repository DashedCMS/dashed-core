<?php

namespace Qubiqx\QcommerceCore;

use Qubiqx\QcommerceCore\Models\Page;
use Qubiqx\QcommerceCore\Observers\PageObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class QcommerceCoreEventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Page::observe(PageObserver::class);
    }
}

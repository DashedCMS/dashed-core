<?php

namespace Dashed\DashedCore\Classes\Caching;

class Cacheables
{
    /**
     * Returns 'on-load' when the current page is being response-cached so that
     * per-visitor Livewire components (cart badge, popups, chat) are rendered as
     * lazy holes instead of being baked into the shared cached HTML.
     *
     * Returns false (eager) when caching is off - identical to current behaviour.
     */
    public static function holeLazy(): string|bool
    {
        return CacheDecision::for(request())->shouldCache() ? 'on-load' : false;
    }
}

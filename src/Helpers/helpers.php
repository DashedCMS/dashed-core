<?php

use Dashed\DashedCore\CMSManager;
use Dashed\DashedCore\SeoManager;
use Dashed\DashedCore\Classes\LinkHelper;
use Dashed\DashedCore\Classes\EmailCapture;

if (! function_exists('cms')) {
    function cms(): CMSManager
    {
        return app(CMSManager::class);
    }
}

if (! function_exists('seo')) {
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}

if (! function_exists('linkHelper')) {
    function linkHelper(): LinkHelper
    {
        return app(LinkHelper::class);
    }
}

if (! function_exists('capturedEmail')) {
    /**
     * Laatste in de sessie gevangen e-mailadres van de bezoeker. Wordt
     * gevuld zodra een email-veld ingevuld + gesubmit wordt (checkout,
     * popup, formulier, nieuwsbrief). Gebruik in blades om bv. een
     * email-input voor te vullen of een persoonlijke greeting te tonen.
     */
    function capturedEmail(): ?string
    {
        return EmailCapture::current();
    }
}

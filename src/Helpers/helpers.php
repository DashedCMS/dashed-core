<?php

use Dashed\DashedCore\CMSManager;
use Dashed\DashedCore\SeoManager;
use Dashed\DashedCore\Classes\LinkHelper;

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

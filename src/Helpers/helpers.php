<?php


use Qubiqx\QcommerceCore\CMSManager;
use Qubiqx\QcommerceCore\FrontendManager;

if (! function_exists('cms')) {
    function cms(): CMSManager
    {
        return app(CMSManager::class);
    }
}

if (! function_exists('frontend')) {
    function frontend(): FrontendManager
    {
        return app(FrontendManager::class);
    }
}

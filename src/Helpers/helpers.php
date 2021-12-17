<?php

use Qubiqx\QcommerceCore\CMSManager;

if (! function_exists('cms')) {
    function cms(): CMSManager
    {
        return app(CMSManager::class);
    }
}

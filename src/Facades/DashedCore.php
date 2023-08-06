<?php

namespace Dashed\DashedCore\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dashed\DashedCore\DashedCore
 */
class DashedCore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dashed-core';
    }
}

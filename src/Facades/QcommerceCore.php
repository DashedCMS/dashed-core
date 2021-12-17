<?php

namespace Qubiqx\QcommerceCore\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Qubiqx\QcommerceCore\QcommerceCore
 */
class QcommerceCore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'qcommerce-core';
    }
}

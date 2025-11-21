<?php

namespace Dashed\DashedCore\Support;

trait MeasuresServiceProvider
{
    protected function logProviderMemory(string $phase): void
    {
        return;
        if (! app()->environment('local')) {
            return;
        }

        $memory = round(memory_get_usage(true) / 1024 / 1024, 2);
        ray(sprintf(
            'PROVIDER MEM [%s::%s]: %s MB',
            static::class,
            $phase,
            $memory,
        ));
        logger()->info(sprintf(
            'PROVIDER MEM [%s::%s]: %s MB',
            static::class,
            $phase,
            $memory,
        ));
    }
}

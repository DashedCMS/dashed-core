<?php

namespace Dashed\DashedCore\Mail\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesEmailLocale
{
    protected function resolveLocale(mixed ...$contexts): string
    {
        foreach ($contexts as $context) {
            if ($context instanceof Model && filled($context->locale ?? null)) {
                return $context->locale;
            }
        }

        return app()->getLocale();
    }
}

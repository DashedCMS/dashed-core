<?php

namespace Dashed\DashedCore\Helpers\Http;

trait UrlBuilderTrait
{
    private function buildUrl(): string
    {
        return app()->make(UrlBuilder::class)->__invoke(...func_get_args());
    }
}

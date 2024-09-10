<?php

namespace Dashed\DashedCore\Helpers\Http;

class UrlBuilder
{
    public function __invoke(string $baseUrl, array $parameters = []): string
    {
        if (count($parameters) === 0) {
            return $baseUrl;
        }

        foreach ($parameters as $parameter => $value) {
            if (! str($baseUrl)->contains('?')) {
                $baseUrl .= '?';
            } else {
                $baseUrl .= '&';
            }

            $baseUrl .= urlencode($parameter).'='.urlencode($value);
        }

        return $baseUrl;
    }
}

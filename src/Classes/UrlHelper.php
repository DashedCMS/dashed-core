<?php

namespace Dashed\DashedCore\Classes;

class UrlHelper
{
    public static function checkUrlResponseCode(string $url): int
    {
        //How are we gonna handle this with multisite?
        if (!self::isAbsolute($url)) {
            $url = url($url);
        }

        if (env('APP_ENV') === 'local') {
            stream_context_set_default([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
        }

        try {
            $headers = get_headers($url . '?disableNotFoundLog');
        } catch (\Exception $e) {
            return 404;
        }

        return (int)substr($headers[0], 9, 3);
    }

    public static function isAbsolute(string $url): bool
    {
        return isset(parse_url($url)['scheme']);
    }
}

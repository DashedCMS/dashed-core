<?php

namespace Dashed\DashedCore\Classes;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class UrlHelper
{
    public static function checkUrlResponseCode(string $url): int
    {
        if (env('APP_ENV') === 'local') {
            stream_context_set_default([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
        }

        $headers = get_headers($url . '?disableNotFoundLog');
        return (int)substr($headers[0], 9, 3);
    }
}

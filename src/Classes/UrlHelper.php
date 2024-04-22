<?php

namespace Dashed\DashedCore\Classes;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class UrlHelper
{
    public static function checkUrlResponseCode(string $url): int
    {
        $headers = get_headers($url);
        return (int)substr($headers[0], 9, 3);
    }
}

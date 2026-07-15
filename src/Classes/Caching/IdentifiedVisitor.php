<?php

namespace Dashed\DashedCore\Classes\Caching;

use Illuminate\Support\Facades\Cookie;

class IdentifiedVisitor
{
    public static function mark(): void
    {
        Cookie::queue('dashed_identified', '1', 60 * 24 * 400);
    }

    public static function unmark(): void
    {
        Cookie::queue(Cookie::forget('dashed_identified'));
    }
}

<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Response;
use Dashed\DashedCore\Classes\RobotsTxtBuilder;

class RobotsTxtController
{
    public function __invoke(): Response
    {
        return response(RobotsTxtBuilder::build(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CsrfTokenController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json(['token' => csrf_token()])
            ->header('Cache-Control', 'no-store');
    }
}

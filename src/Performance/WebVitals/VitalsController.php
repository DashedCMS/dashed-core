<?php

namespace Dashed\DashedCore\Performance\WebVitals;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class VitalsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! config('dashed-core.performance.web_vitals_enabled')) {
            return response()->json(null, 204);
        }

        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'name' => 'required|string|in:LCP,CLS,INP,FCP,TTFB',
            'value' => 'required|numeric',
            'rating' => 'nullable|string|in:good,needs-improvement,poor',
            'url' => 'required|string|max:500',
            'device' => 'required|string|in:mobile,desktop',
            'site' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'invalid'], 422);
        }

        $normalizedUrl = UrlNormalizer::normalize($data['url']);

        if ($normalizedUrl === '') {
            return response()->json(null, 204);
        }

        StoreVitalsJob::dispatch([
            'site_id' => $data['site'] ?? null,
            'metric' => $data['name'],
            'value' => (float) $data['value'],
            'rating' => $data['rating'] ?? null,
            'url' => $normalizedUrl,
            'device' => $data['device'],
        ]);

        return response()->json(null, 204);
    }
}

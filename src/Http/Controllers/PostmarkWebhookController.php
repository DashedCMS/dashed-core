<?php

// packages/dashed/dashed-core/src/Http/Controllers/PostmarkWebhookController.php

namespace Dashed\DashedCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Mail\PostmarkEventHandler;

class PostmarkWebhookController extends Controller
{
    public function __invoke(Request $request, PostmarkEventHandler $handler): JsonResponse
    {
        $secret = config('dashed-core.sent_emails.postmark_webhook_secret');

        if ($secret && ! $this->authorized($request, $secret)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $handler->handle($request->all());
        } catch (\Throwable $e) {
            Log::error('Postmark webhook verwerking mislukt: ' . $e->getMessage());
        }

        return response()->json(['message' => 'ok'], 200);
    }

    protected function authorized(Request $request, string $secret): bool
    {
        // Postmark stuurt basic-auth; de query-param is een fallback voor tests
        // en simpele setups.
        if (hash_equals($secret, (string) $request->query('secret', ''))) {
            return true;
        }

        return hash_equals($secret, (string) $request->getPassword())
            || hash_equals($secret, (string) $request->getUser());
    }
}

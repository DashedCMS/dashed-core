<?php

namespace Dashed\DashedCore\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fingerprints inbound webhook requests to identify which payment provider
 * (mollie / paynl / multisafepay) they came from.
 *
 * All three providers share the same `dashed.frontend.checkout.exchange*`
 * route. When the middleware is configured with `:auto`, this class chooses
 * the provider slug used to scope the `WebhookLog` row.
 */
class WebhookProviderDetector
{
    public function detect(Request $request): ?string
    {
        // Mollie: payment ids look like `tr_XXXXXXXX` and arrive in the
        // `id` query/body param.
        $mollieId = (string) $request->input('id', '');
        if ($mollieId !== '' && Str::startsWith($mollieId, 'tr_')) {
            return 'mollie';
        }

        // PayNL: ships an `orderId` field or an `X-PayNL-Signature` header.
        if ($request->headers->has('X-PayNL-Signature') || $request->input('orderId') !== null) {
            return 'paynl';
        }

        // Multisafepay: ships a `transactionid` query/body param or a typed
        // `type=initialized|completed|...` flag.
        if ($request->input('transactionid') !== null || in_array($request->input('type'), ['initialized', 'completed', 'cancelled', 'expired'], true)) {
            return 'multisafepay';
        }

        return null;
    }
}

<?php

namespace Dashed\DashedCore\Webhooks;

use Closure;
use Illuminate\Http\Request;

/**
 * Registry of per-provider closures that extract a canonical `event_id`
 * from an inbound webhook request. The middleware queries this resolver
 * to decide which UNIQUE (provider, event_id) pair to insert.
 *
 * Bound as a singleton from `DashedCoreServiceProvider`. Provider packages
 * register their extractors during `bootingPackage()`:
 *
 *     app(WebhookEventIdResolver::class)->extend(
 *         'mollie',
 *         fn (Request $r) => (string) $r->input('id'),
 *     );
 */
class WebhookEventIdResolver
{
    /** @var array<string, Closure> */
    protected array $extractors = [];

    public function extend(string $provider, Closure $extractor): void
    {
        $this->extractors[$provider] = $extractor;
    }

    /**
     * Resolve the canonical event_id for a given provider's request. When
     * no extractor is registered (or it returns null/empty) and
     * `$fallbackToHash` is true, returns a SHA-256-of-body fingerprint so
     * the unique-index still gets a stable value.
     */
    public function resolve(string $provider, Request $request, bool $fallbackToHash = true): ?string
    {
        $extractor = $this->extractors[$provider] ?? null;

        if ($extractor !== null) {
            $value = $extractor($request);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        if (! $fallbackToHash) {
            return null;
        }

        $body = (string) $request->getContent();
        if ($body === '') {
            return null;
        }

        return substr(hash('sha256', $body), 0, 32);
    }

    public function hasExtractor(string $provider): bool
    {
        return isset($this->extractors[$provider]);
    }
}

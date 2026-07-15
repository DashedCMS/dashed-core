<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Dashed\DashedCore\Classes\CacheProfile;
use Dashed\DashedCore\Classes\FragmentCache;
use Dashed\DashedCore\Classes\Caching\CacheDecision;
use Dashed\DashedCore\Classes\Caching\CloudflareConfig;

class ResponseCache
{
    /**
     * Cookie names that the framework always sets and are NOT "identity" cookies.
     * If a response sets *only* these, it is safe to cache.
     * NOTE: uses config('session.cookie') at runtime so we match the actual session
     * cookie name (e.g. "dashed_cms_session"), not the Laravel default "laravel_session".
     */
    private function frameworkCookieNames(): array
    {
        return [
            config('session.cookie', 'laravel_session'),
            'XSRF-TOKEN',
            'laravel_token',
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $decision = CacheDecision::for($request);

        // Fix 2: compute once to avoid repeated Customsetting lookups on the hot path.
        $edgeConfigured = CacheProfile::forSite()->edgeEnabled()
            && CloudflareConfig::for()->configured();

        // ---- BYPASS -------------------------------------------------------
        if (! $decision->shouldCache()) {
            /** @var Response $response */
            $response = $next($request);
            $response->headers->set('X-Dashed-Cache', 'BYPASS ' . $decision->reason());
            $this->applyEdgeHeaders($response, $decision, false, $edgeConfigured);

            return $response;
        }

        // ---- CACHE HIT ----------------------------------------------------
        $cacheKey = $decision->cacheKey();
        $tags = $decision->tags();

        $storedHtml = FragmentCache::supportsTags()
            ? Cache::tags($tags)->get($cacheKey)
            : Cache::get($cacheKey);

        if ($storedHtml !== null) {
            $html = $this->injectCacheHitMeta($storedHtml);

            $hitResponse = response($html, 200)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Dashed-Cache', 'HIT');
            // HIT: stored body was anonymous - always safe for the edge.
            $this->applyEdgeHeaders($hitResponse, $decision, true, $edgeConfigured);

            return $hitResponse;
        }

        // ---- CACHE MISS ---------------------------------------------------
        /** @var Response $response */
        $response = $next($request);

        // Fix 1: compute once, reuse for both the Redis write and the edge gate.
        $safeToStore = $this->isSafeToStore($response);

        if ($safeToStore) {
            $html = $response->getContent();

            if (FragmentCache::supportsTags()) {
                Cache::tags($tags)->put($cacheKey, $html, $decision->ttl());
            } else {
                Cache::put($cacheKey, $html, $decision->ttl());
            }
        }

        $response->headers->set('X-Dashed-Cache', 'MISS');
        // Fix 1: only allow public edge header when the response was actually safe to store.
        $this->applyEdgeHeaders($response, $decision, $safeToStore, $edgeConfigured);

        return $response;
    }

    /**
     * Apply edge Cache-Control headers to the outgoing response.
     *
     * Rules:
     * - $eligibleForEdge must be true (callers derive this from shouldCache() AND,
     *   on the MISS path, isSafeToStore()), the site profile enables edge caching,
     *   and Cloudflare credentials are configured: set
     *   `Cache-Control: public, s-maxage={ttl}, max-age=0`
     *   so that Cloudflare caches the response at the edge while browsers
     *   always revalidate (max-age=0 prevents browser caches from reusing a
     *   stale copy for a later logged-in visitor).
     * - In all other cases (BYPASS, edge disabled, CF not configured, or response
     *   failed isSafeToStore): set `Cache-Control: private, no-store` so shared
     *   caches never cache the response.
     *
     * SAFETY GUARANTEE: `public` is ONLY set when $eligibleForEdge is true.
     * On the HIT path this is always true (stored body was anonymous).
     * On the MISS path this requires isSafeToStore() to pass - ensuring we never
     * tell Cloudflare to cache a response the origin refused to store.
     * BYPASS responses always receive `private, no-store`.
     *
     * @param bool $eligibleForEdge  HIT: shouldCache(); MISS: shouldCache() && safeToStore; BYPASS: false
     * @param bool $edgeConfigured   Pre-computed: edgeEnabled() && CF configured (computed once in handle())
     */
    private function applyEdgeHeaders(
        Response $response,
        CacheDecision $decision,
        bool $eligibleForEdge,
        bool $edgeConfigured,
    ): void {
        if ($eligibleForEdge && $edgeConfigured) {
            $response->headers->set(
                'Cache-Control',
                'public, s-maxage=' . $decision->ttl() . ', max-age=0',
            );

            return;
        }

        $response->headers->set('Cache-Control', 'private, no-store');
    }

    /**
     * Inject <meta name="dashed-cache" content="hit"> just before </head>.
     * This activates the CSRF-refresh snippet (Task 3) for cache-hit responses.
     * If the response has no </head>, the meta is prepended to the content.
     */
    private function injectCacheHitMeta(string $html): string
    {
        $meta = '<meta name="dashed-cache" content="hit">';
        $pos = stripos($html, '</head>');

        if ($pos === false) {
            return $meta . $html;
        }

        // Insert just before </head> (preserve original case of the closing tag)
        return substr($html, 0, $pos) . $meta . substr($html, $pos);
    }

    /**
     * Only store a response when:
     * - Status code is exactly 200
     * - It is not a redirect
     * - Content-Type is text/html (JSON, XML, binary responses are not cached)
     * - The response does not set any identity Set-Cookie headers beyond the
     *   standard framework session/XSRF cookies
     * - Laravel's cookie queue (AddQueuedCookiesToResponse runs AFTER this
     *   middleware on the outbound path) does not contain any non-framework
     *   cookies. This closes the timing gap where a cart-token queued via
     *   Cookie::queue() would not yet appear in Set-Cookie headers.
     */
    private function isSafeToStore(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if ($response->isRedirect()) {
            return false;
        }

        // Only cache HTML responses - not JSON, binary, etc.
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return false;
        }

        $allowed = $this->frameworkCookieNames();

        // Check Set-Cookie headers already flushed onto the response.
        foreach ($response->headers->all('set-cookie') as $cookieHeader) {
            $cookieName = trim((string) strtok($cookieHeader, '='));
            if (! in_array($cookieName, $allowed, true)) {
                return false;
            }
        }

        // Check Laravel's cookie queue (cookies not yet flushed to the response
        // because AddQueuedCookiesToResponse is OUTER and runs after us).
        foreach (app('cookie')->getQueuedCookies() as $cookie) {
            if (! in_array($cookie->getName(), $allowed, true)) {
                return false;
            }
        }

        return true;
    }
}

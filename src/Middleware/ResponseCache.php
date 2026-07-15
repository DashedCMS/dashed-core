<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Dashed\DashedCore\Classes\FragmentCache;
use Dashed\DashedCore\Classes\Caching\CacheDecision;

class ResponseCache
{
    /**
     * Cookie names that the framework always sets and are NOT "identity" cookies.
     * If a response sets *only* these, it is safe to cache.
     */
    private const FRAMEWORK_COOKIE_NAMES = [
        'laravel_session',
        'XSRF-TOKEN',
        'laravel_token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $decision = CacheDecision::for($request);

        // ---- BYPASS -------------------------------------------------------
        if (! $decision->shouldCache()) {
            /** @var Response $response */
            $response = $next($request);
            $response->headers->set('X-Dashed-Cache', 'BYPASS ' . $decision->reason());

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

            return response($html, 200)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Dashed-Cache', 'HIT');
        }

        // ---- CACHE MISS ---------------------------------------------------
        /** @var Response $response */
        $response = $next($request);

        if ($this->isSafeToStore($response)) {
            $html = $response->getContent();

            if (FragmentCache::supportsTags()) {
                Cache::tags($tags)->put($cacheKey, $html, $decision->ttl());
            } else {
                Cache::put($cacheKey, $html, $decision->ttl());
            }
        }

        $response->headers->set('X-Dashed-Cache', 'MISS');

        return $response;
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
     * - The response does not set any identity Set-Cookie headers beyond the
     *   standard framework session/XSRF cookies. If it sets "dashed_identified",
     *   a cart-token cookie, or any other application cookie we do NOT store —
     *   the visitor is being identified and should not be served cached content.
     */
    private function isSafeToStore(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if ($response->isRedirect()) {
            return false;
        }

        // Inspect Set-Cookie headers for identity cookies.
        $setCookieHeaders = $response->headers->all('set-cookie');

        foreach ($setCookieHeaders as $cookieHeader) {
            // Extract the cookie name (everything before the first = sign)
            $cookieName = strtok($cookieHeader, '=');
            $cookieName = trim($cookieName ?? '');

            if (! in_array($cookieName, self::FRAMEWORK_COOKIE_NAMES, true)) {
                // An application-level cookie is being set — conservative bypass
                return false;
            }
        }

        return true;
    }
}

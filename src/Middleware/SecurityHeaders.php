<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zet de HTTP-beveiligingsheaders op elk antwoord, website en CMS. Globaal
 * aangemeld door dashed-core, dus elk klantproject krijgt ze zonder eigen
 * configuratie; per header uit te zetten of aan te passen in
 * dashed-core.security_headers. Een header die de applicatie zelf al zette
 * blijft staan.
 *
 * Geen Content-Security-Policy: de instelling "extra scripts" laat inline
 * scripts toe, en dat vraagt eerst een nonce-strategie.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! filter_var(config('dashed-core.security_headers.enabled', true), FILTER_VALIDATE_BOOL)) {
            return $response;
        }

        foreach (static::headersFor($request) as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    public static function headersFor(Request $request): array
    {
        $config = (array) config('dashed-core.security_headers', []);

        $headers = [
            'X-Frame-Options' => $config['x_frame_options'] ?? null,
            'X-Content-Type-Options' => $config['x_content_type_options'] ?? null,
            'Referrer-Policy' => $config['referrer_policy'] ?? null,
            'Permissions-Policy' => $config['permissions_policy'] ?? null,
        ];

        // HSTS over http is zinloos en op een lokale omgeving schadelijk: de
        // browser onthoudt hem een jaar, ook voor andere projecten op .test.
        if ($request->isSecure() && ! app()->isLocal()) {
            $headers['Strict-Transport-Security'] = $config['strict_transport_security'] ?? null;
        }

        return array_filter(
            array_map(fn ($value) => is_string($value) ? trim($value) : $value, $headers),
            fn ($value) => is_string($value) && $value !== '',
        );
    }
}

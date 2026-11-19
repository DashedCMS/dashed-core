<?php

namespace Dashed\DashedCore\Classes;

use Closure;

/**
 * Extra OAuth-koppelingen met Google die over dezelfde client-id, hetzelfde
 * client-secret en dezelfde callback-URL lopen als de Business-koppeling.
 *
 * Een pakket meldt hier een flow aan met eigen scopes, een plek om het
 * refresh-token te bewaren en een scherm om naar terug te keren. Dezelfde
 * callback-URL betekent dat een beheerder in Google Cloud geen tweede
 * redirect-URI hoeft toe te voegen.
 *
 * De flow `business` bestaat niet in dit register: dat is de standaardroute
 * van GoogleOAuthController en die blijft precies zoals hij was.
 */
class GoogleOAuthFlows
{
    /** @var array<string, array{scopes: array<int, string>, store: Closure, return: Closure}> */
    protected static array $flows = [];

    /**
     * @param  array<int, string>  $scopes
     * @param  Closure(array $token, ?string $siteId): void  $store  krijgt het tokenantwoord van Google
     * @param  Closure(?string $siteId): string  $returnUrl
     */
    public static function register(string $key, array $scopes, Closure $store, Closure $returnUrl): void
    {
        static::$flows[$key] = [
            'scopes' => array_values($scopes),
            'store' => $store,
            'return' => $returnUrl,
        ];
    }

    public static function has(string $key): bool
    {
        return isset(static::$flows[$key]);
    }

    /** @return array{scopes: array<int, string>, store: Closure, return: Closure}|null */
    public static function get(string $key): ?array
    {
        return static::$flows[$key] ?? null;
    }

    public static function flush(): void
    {
        static::$flows = [];
    }
}

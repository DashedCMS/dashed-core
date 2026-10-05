<?php

namespace Dashed\DashedCore\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Weigert Livewire-updates waarin de client zelf een "synthetisch tuple" meestuurt:
 * [waarde, ['s' => 'clctn', 'class' => ...]]. Die vorm hoort alleen in de (ondertekende)
 * snapshot te staan; de Livewire-client stuurt in `updates` uitsluitend kale waarden.
 *
 * Sinds de RCE in Livewire (CVE-2025-54068) vuren scanners deze payloads op elke
 * publieke property van elk component af. Livewire zelf is gepatcht, dus de aanval
 * slaagt niet, maar de rommel belandde wel in de properties en liep daarna stuk in
 * componenten en views (array offset on int, htmlspecialchars, whereIn, ...). Hier
 * stopt het verzoek met een 400 voordat er een component wordt aangeraakt.
 */
class RejectTamperedLivewireUpdates
{
    private const MAX_DEPTH = 64;

    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('POST') && $request->route()?->named('*livewire.update')) {
            foreach ((array) $request->input('components', []) as $component) {
                if (is_array($component) && self::containsSyntheticTuple($component['updates'] ?? null)) {
                    abort(400);
                }
            }
        }

        return $next($request);
    }

    public static function containsSyntheticTuple(mixed $value, int $depth = 0): bool
    {
        if (! is_array($value)) {
            return false;
        }

        // Zo diep nest geen enkel echt formulier; niet verder afdalen maar weigeren.
        if ($depth > self::MAX_DEPTH) {
            return true;
        }

        if (count($value) === 2
            && array_is_list($value)
            && is_array($value[1])
            && is_string($value[1]['s'] ?? null)
        ) {
            return true;
        }

        foreach ($value as $child) {
            if (self::containsSyntheticTuple($child, $depth + 1)) {
                return true;
            }
        }

        return false;
    }
}

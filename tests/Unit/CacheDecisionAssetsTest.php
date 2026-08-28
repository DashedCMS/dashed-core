<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;
use Dashed\DashedCore\Classes\Caching\CacheDecision;

/**
 * De paginacache bewaart volledige HTML, en daarin staan de gehashte
 * bestandsnamen van de frontend-assets. Gooit een nieuwe build die bestanden
 * weg, dan blijft de cache HTML uitserveren die naar een verdwenen stylesheet
 * wijst: 404 op de CSS en dus een pagina zonder opmaak. Dat overkwam
 * leesdetectives op 26-8; alleen de twee pagina's die al in de cache stonden
 * waren stuk en de rest was goed — precies het verwarrende beeld dat bij dit
 * soort caching hoort.
 *
 * De sleutel draagt daarom de vingerafdruk van het Vite-manifest: een nieuwe
 * build levert vanzelf nieuwe sleutels op en de oude HTML wordt nooit meer
 * gevonden.
 */
it('geeft dezelfde pagina een andere sleutel na een nieuwe assetbuild', function () {
    // once(): anders vangt de eerste verwachting ook de tweede aanroep op en
    // meet de test niets.
    Vite::shouldReceive('manifestHash')->once()->andReturn('eerste-build');
    $voor = CacheDecision::cacheKeyFor('site', 'nl', '/blog');

    Vite::shouldReceive('manifestHash')->once()->andReturn('tweede-build');
    $na = CacheDecision::cacheKeyFor('site', 'nl', '/blog');

    expect($voor)->not->toBe($na);
});

it('houdt de sleutel gelijk zolang de assets niet veranderen', function () {
    Vite::shouldReceive('manifestHash')->andReturn('zelfde-build');

    expect(CacheDecision::cacheKeyFor('site', 'nl', '/blog'))
        ->toBe(CacheDecision::cacheKeyFor('site', 'nl', '/blog'));
});

it('houdt paden, locales en sites uit elkaar', function () {
    Vite::shouldReceive('manifestHash')->andReturn('zelfde-build');

    expect(CacheDecision::cacheKeyFor('site', 'nl', '/blog'))
        ->not->toBe(CacheDecision::cacheKeyFor('site', 'nl', '/over-ons'))
        ->and(CacheDecision::cacheKeyFor('site', 'nl', '/blog'))
        ->not->toBe(CacheDecision::cacheKeyFor('site', 'en', '/blog'))
        ->and(CacheDecision::cacheKeyFor('site', 'nl', '/blog'))
        ->not->toBe(CacheDecision::cacheKeyFor('tweede-site', 'nl', '/blog'));
});

/**
 * Zonder build (verse installatie) of met de dev-server aan geeft Laravel geen
 * hash terug. Dat mag geen fout opleveren en moet een bruikbare sleutel houden.
 */
it('werkt zonder gebouwde assets', function () {
    Vite::shouldReceive('manifestHash')->andReturn(null);

    expect(CacheDecision::cacheKeyFor('site', 'nl', '/blog'))
        ->toBeString()
        ->toStartWith('response:site:nl:');
});

<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Dashed\DashedCore\Classes\Caching\EdgePurgeThrottle;

function throttle(int $window = 30): EdgePurgeThrottle
{
    return new EdgePurgeThrottle(new Repository(new ArrayStore()), $window);
}

/**
 * Een zone-purge is grofmazig (purge_everything) en duur. Tien wijzigingen in
 * hetzelfde request leverden voorheen tien volledige purges op. Deze throttle
 * knijpt dat af tot: meteen eentje, en daarna hooguit één naloper zodat de
 * laatste wijziging niet verloren gaat.
 */
it('laat de eerste purge in een venster meteen door', function () {
    expect(throttle()->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW);
});

it('plant na de eerste purge precies een naloper, geen stapel', function () {
    $throttle = throttle();

    expect($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW)
        ->and($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::TRAILING)
        ->and($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::SKIP)
        ->and($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::SKIP);
});

/**
 * Zonder naloper zou de laatste wijziging binnen het venster stilletjes op de
 * edge blijven staan. Dat is precies het soort verouderde content dat de purge
 * moest voorkomen, dus de naloper is geen luxe.
 */
it('vraagt om een naloper zodat de laatste wijziging alsnog landt', function () {
    $throttle = throttle();
    $throttle->decide('hay-united');

    expect($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::TRAILING);
});

it('houdt sites uit elkaar', function () {
    $throttle = throttle();

    expect($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW)
        ->and($throttle->decide('tweede-site'))->toBe(EdgePurgeThrottle::NOW);
});

it('laat na afloop van het venster weer een directe purge toe', function () {
    $throttle = throttle(window: 30);

    Carbon::setTestNow(Carbon::parse('2026-08-17 12:00:00'));
    expect($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW)
        ->and($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::TRAILING);

    Carbon::setTestNow(Carbon::parse('2026-08-17 12:00:31'));
    expect($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW);

    Carbon::setTestNow();
});

/**
 * Cache::add() geeft bij een TTL van 0 of minder altijd false terug. Zonder
 * expliciete guard zou een venster van 0 dus niet 'niet knijpen' betekenen maar
 * 'nooit meer purgen' -- de edge zou dan blijvend verouderde content serveren
 * zonder dat er iets in de logs opduikt.
 */
it('schakelt zichzelf uit in plaats van alles te blokkeren bij een leeg venster', function () {
    $throttle = throttle(window: 0);

    expect($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW)
        ->and($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW)
        ->and($throttle->decide('hay-united'))->toBe(EdgePurgeThrottle::NOW);
});

it('rapporteert de vertraging waarmee de naloper ingepland moet worden', function () {
    expect(throttle(window: 45)->delaySeconds())->toBe(45);
});

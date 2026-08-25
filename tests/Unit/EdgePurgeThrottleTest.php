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

/**
 * Het venster stond op 30 seconden: hooguit twee zone-purges per halve minuut,
 * dus nog altijd een paar honderd per uur als er de hele dag door producten en
 * instellingen worden weggeschreven. Cloudflare purgt met purge_everything, dus
 * elke purge kost de hele zone zijn cache. Vijf minuten is de nieuwe standaard;
 * de eerste wijziging gaat nog steeds meteen door, alleen een reeks erna wacht.
 */
it('knijpt standaard af op een venster van vijf minuten', function () {
    expect(EdgePurgeThrottle::WINDOW_SECONDS)->toBe(300)
        ->and((new EdgePurgeThrottle(new Repository(new ArrayStore())))->delaySeconds())->toBe(300);
});

/**
 * De configuratiewaarde komt uit een gepubliceerd config-bestand dat in oudere
 * projecten de sleutel nog niet heeft. Die krijgen dus null binnen en moeten op
 * de standaard terugvallen in plaats van op een venster van 0 -- dat laatste
 * zou de throttle uitschakelen en precies de storm terugbrengen.
 */
it('valt terug op de standaard bij een ontbrekende of onzinnige instelling', function () {
    expect(EdgePurgeThrottle::windowFromConfig(null))->toBe(300)
        ->and(EdgePurgeThrottle::windowFromConfig(''))->toBe(300)
        ->and(EdgePurgeThrottle::windowFromConfig('geen getal'))->toBe(300)
        ->and(EdgePurgeThrottle::windowFromConfig(-5))->toBe(300);
});

it('neemt een ingestelde vensterlengte over', function () {
    expect(EdgePurgeThrottle::windowFromConfig(60))->toBe(60)
        ->and(EdgePurgeThrottle::windowFromConfig('120'))->toBe(120);
});

/**
 * Nul is een bewuste keuze -- "niet knijpen" -- en geen ontbrekende waarde, dus
 * die moet blijven staan; decide() heeft daar zijn eigen guard voor.
 */
it('respecteert een venster van nul als uitschakelaar', function () {
    expect(EdgePurgeThrottle::windowFromConfig(0))->toBe(0);
});

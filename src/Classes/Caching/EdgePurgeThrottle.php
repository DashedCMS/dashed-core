<?php

namespace Dashed\DashedCore\Classes\Caching;

use Illuminate\Contracts\Cache\Repository;

/**
 * Knijpt het aantal Cloudflare zone-purges af.
 *
 * CloudflarePurger purgt met purge_everything: grofmazig en duur. Elke
 * afzonderlijke save van een instelling, vertaling, pagina of menu dispatchte
 * zijn eigen purge, dus tien wijzigingen in één request werden tien volledige
 * purges.
 *
 * Deze throttle laat de eerste purge in een venster meteen door -- een
 * inhoudelijke wijziging mag niet onnodig wachten -- en plant daarna hooguit
 * één naloper. Die naloper is er niet voor de netheid: zonder hem zou de laatste
 * wijziging binnen het venster nooit gepurged worden en bleef de edge stil
 * verouderd, precies wat de purge moest voorkomen.
 */
class EdgePurgeThrottle
{
    public const NOW = 'now';
    public const TRAILING = 'trailing';
    public const SKIP = 'skip';

    public const WINDOW_SECONDS = 30;

    public function __construct(
        protected readonly Repository $cache,
        protected readonly int $window = self::WINDOW_SECONDS,
    ) {
    }

    /**
     * Geeft terug wat er met deze purge-aanvraag moet gebeuren.
     *
     * NOW      -> nu dispatchen
     * TRAILING -> dispatchen met delaySeconds() vertraging
     * SKIP     -> niets doen, er staat al een naloper klaar
     */
    public function decide(string $siteId): string
    {
        // Zonder venster is er niets te knijpen. Deze tak is geen formaliteit:
        // Cache::add() geeft bij een TTL van 0 of minder altijd false terug, dus
        // zonder deze guard zou een venster van 0 elke purge overslaan in plaats
        // van ze allemaal door te laten -- de stilste denkbare storing.
        if ($this->window <= 0) {
            return self::NOW;
        }

        // add() is atomair: alleen de eerste aanvrager binnen het venster krijgt
        // true terug, ook als er meerdere workers tegelijk purgen.
        if ($this->cache->add($this->key('lead', $siteId), true, $this->window)) {
            return self::NOW;
        }

        if ($this->cache->add($this->key('trail', $siteId), true, $this->window)) {
            return self::TRAILING;
        }

        return self::SKIP;
    }

    public function delaySeconds(): int
    {
        return $this->window;
    }

    protected function key(string $kind, string $siteId): string
    {
        return 'dashed:edge-purge:' . $kind . ':' . $siteId;
    }
}

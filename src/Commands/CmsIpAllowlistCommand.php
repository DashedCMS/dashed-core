<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\CmsIpAllowlist;

/**
 * De ontsnapping voor wie zichzelf toch heeft buitengesloten: het scherm
 * weigert een lijst zonder het eigen adres, maar een kantoor kan van
 * IP-adres wisselen nadat de lijst is opgeslagen.
 */
class CmsIpAllowlistCommand extends Command
{
    protected $signature = 'dashed:cms-ip-allowlist
        {--add=* : Adres of reeks (CIDR) om toe te voegen}
        {--name= : Naam bij de toegevoegde adressen, om terug te zien van wie ze zijn}
        {--clear : Maak de lijst leeg, zodat het CMS weer vanaf elk adres bereikbaar is}';

    protected $description = 'Toon, vul aan of leeg de lijst met IP-adressen waarvandaan het CMS bereikbaar is';

    public function handle(): int
    {
        if ($this->option('clear')) {
            CmsIpAllowlist::clear();
            $this->info('De lijst is leeg; het CMS is weer vanaf elk adres bereikbaar.');

            return self::SUCCESS;
        }

        $name = (string) $this->option('name');
        $toAdd = array_map(
            fn (array $entry) => ['name' => $name, 'ip' => $entry['ip']],
            CmsIpAllowlist::parse(implode("\n", (array) $this->option('add'))),
        );

        if ($toAdd) {
            $invalid = CmsIpAllowlist::invalidEntries($toAdd);

            if ($invalid) {
                $this->error('Ongeldig: ' . implode(', ', $invalid));

                return self::FAILURE;
            }

            CmsIpAllowlist::save(array_merge(CmsIpAllowlist::entries(), $toAdd));
        }

        $entries = CmsIpAllowlist::entries();

        if (! $entries) {
            $this->line('De lijst is leeg; het CMS is vanaf elk adres bereikbaar.');

            return self::SUCCESS;
        }

        $this->line('Het CMS is alleen bereikbaar vanaf:');

        foreach (CmsIpAllowlist::labels($entries) as $label) {
            $this->line('  ' . $label);
        }

        return self::SUCCESS;
    }
}

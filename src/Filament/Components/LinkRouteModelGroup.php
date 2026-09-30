<?php

namespace Dashed\DashedCore\Filament\Components;

use Illuminate\Support\Arr;
use Filament\Schemas\Components\Group;

/**
 * De groep in LinkHelper::field() met alleen de keuzelijst van het gekozen
 * linktype.
 *
 * Een id van een niet gekozen type heeft daardoor geen veld meer, en Filament
 * laat bij opslaan alleen de staat van een verborgen veld vallen. Binnen een
 * Builder of Repeater houdt de array-regel op het hele item zo'n losse sleutel
 * anders gewoon vast. Toen elk type nog een verborgen keuzelijst had, viel die
 * id bij opslaan weg; deze groep haalt hem weg zodat de opgeslagen data
 * hetzelfde blijft.
 */
class LinkRouteModelGroup extends Group
{
    protected string $linkPrefix = 'url';

    /** @var array<string> */
    protected array $routeModelKeys = [];

    public function linkPrefix(string $prefix): static
    {
        $this->linkPrefix = $prefix;

        return $this;
    }

    /**
     * @param  array<string>  $keys
     */
    public function routeModelKeys(array $keys): static
    {
        $this->routeModelKeys = $keys;

        return $this;
    }

    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        parent::dehydrateState($state, $isDehydrated);

        $basePath = $this->getContainer()->getStatePath();
        $path = fn (string $name): string => filled($basePath) ? "{$basePath}.{$name}" : $name;
        $type = Arr::get($state, $path("{$this->linkPrefix}_type"));

        foreach ($this->routeModelKeys as $key) {
            if ($key !== $type) {
                Arr::forget($state, $path("{$this->linkPrefix}_{$key}_id"));
            }
        }
    }
}

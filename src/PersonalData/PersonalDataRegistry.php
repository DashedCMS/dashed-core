<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

use InvalidArgumentException;

/**
 * Alle aangemelde providers. Klassenamen, pas bij gebruik geïnstantieerd,
 * zodat aanmelden in de boot geen vertaling of database nodig heeft.
 */
class PersonalDataRegistry
{
    /** @var array<string, class-string<PersonalDataProvider>|PersonalDataProvider> */
    protected array $providers = [];

    /**
     * @param  class-string<PersonalDataProvider>|PersonalDataProvider  $provider
     */
    public function register(string|PersonalDataProvider $provider): void
    {
        $name = is_string($provider) ? $provider : $provider::class;

        if (is_string($provider) && ! is_subclass_of($provider, PersonalDataProvider::class)) {
            throw new InvalidArgumentException("{$provider} must extend " . PersonalDataProvider::class);
        }

        $this->providers[$name] = $provider;
    }

    /**
     * Gesorteerd op prioriteit (laag eerst), daarna op aanmeldvolgorde.
     *
     * @return array<int, PersonalDataProvider>
     */
    public function all(): array
    {
        $instances = [];
        foreach ($this->providers as $name => $provider) {
            $instances[] = is_string($provider) ? app($provider) : $provider;
        }

        usort($instances, fn (PersonalDataProvider $a, PersonalDataProvider $b) => $a->priority() <=> $b->priority());

        return $instances;
    }

    public function has(string $class): bool
    {
        return isset($this->providers[$class]);
    }

    public function flush(): void
    {
        $this->providers = [];
    }
}

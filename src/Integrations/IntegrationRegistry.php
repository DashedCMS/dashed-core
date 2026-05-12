<?php

namespace Dashed\DashedCore\Integrations;

/**
 * In-memory registry of admin integrations. Bound as a singleton by
 * DashedCoreServiceProvider in the register() phase so that every package
 * SP's bootingPackage() can register its integration into the same instance.
 */
class IntegrationRegistry
{
    /** @var array<string, IntegrationDefinition> */
    protected array $definitions = [];

    public function register(IntegrationDefinition $definition): IntegrationDefinition
    {
        $this->definitions[$definition->slug] = $definition;
        return $definition;
    }

    public function get(string $slug): ?IntegrationDefinition
    {
        return $this->definitions[$slug] ?? null;
    }

    /** @return array<string, IntegrationDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Group integrations by their `category`, sorted by category then by
     * label so the dashboard renders the same order on every page load.
     *
     * @return array<string, array<int, IntegrationDefinition>>
     */
    public function byCategory(): array
    {
        $grouped = [];
        foreach ($this->definitions as $def) {
            $grouped[$def->category][] = $def;
        }

        ksort($grouped);
        foreach ($grouped as &$bucket) {
            usort($bucket, fn (IntegrationDefinition $a, IntegrationDefinition $b) => strcmp($a->label, $b->label));
        }

        return $grouped;
    }

    public function forget(string $slug): void
    {
        unset($this->definitions[$slug]);
    }

    public function flush(): void
    {
        $this->definitions = [];
    }
}

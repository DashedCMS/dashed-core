<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

/**
 * In-memory verzameling van alles wat opgeruimd wordt. Gebonden als singleton
 * in de register-fase, zodat elke ServiceProvider in zijn bootingPackage() in
 * dezelfde instantie schrijft.
 */
class RetentionRegistry
{
    /** @var array<string, Retention> */
    protected array $entries = [];

    public function registreer(Retention $retention): void
    {
        $this->entries[$retention->sleutel()] = $retention;
    }

    /** @return array<int, Retention> */
    public function alles(): array
    {
        return array_values($this->entries);
    }

    public function vind(string $sleutel): ?Retention
    {
        return $this->entries[$sleutel] ?? null;
    }

    public function flush(): void
    {
        $this->entries = [];
    }
}

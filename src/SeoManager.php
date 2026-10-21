<?php

namespace Dashed\DashedCore;

/**
 * Per verzoek (scoped binding in DashedCoreServiceProvider). Een statische
 * opslag lekte tussen verzoeken onder Octane en in een wachtrij-worker.
 */
class SeoManager
{
    protected const DEFAULTS = [
        'metaTitle' => '',
        'metaDescription' => '',
        'metaImage' => '',
        'ogType' => 'website',
        'twitterSite' => '',
        'twitterCreator' => '',
        'webmasterTags' => [],
        'robots' => 'index, follow',
        'canonical' => '',
        'schemas' => [],
        'customStructuredData' => [],
    ];

    protected array $metaData = self::DEFAULTS;

    /**
     * Historisch gedrag: een falsy $value leest. Een waarde op leeg zetten
     * gaat via set().
     */
    public function metaData(string $name, string|array|null $value = null): self|string|array
    {
        if (! $value) {
            return $this->metaData[$name] ?? '';
        }

        $this->metaData[$name] = $value;

        return $this;
    }

    public function set(string $name, mixed $value): static
    {
        $this->metaData[$name] = $value;

        return $this;
    }

    public function all(): array
    {
        return $this->metaData;
    }

    public function reset(): void
    {
        $this->metaData = self::DEFAULTS;
    }
}

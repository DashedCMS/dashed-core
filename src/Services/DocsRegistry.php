<?php

namespace Dashed\DashedCore\Services;

class DocsRegistry
{
    protected array $resources = [];

    protected array $settingsPages = [];

    protected array $topics = [];

    public function registerResource(string $resource, array $doc): void
    {
        $this->resources[$resource] = $doc;
    }

    public function registerSettingsPage(string $page, array $doc): void
    {
        $this->settingsPages[$page] = $doc;
    }

    public function registerTopic(string $key, array $doc): void
    {
        $this->topics[$key] = $doc;
    }

    public function getForResource(string $resource): ?array
    {
        return $this->resources[$resource] ?? null;
    }

    public function getForSettingsPage(string $page): ?array
    {
        return $this->settingsPages[$page] ?? null;
    }

    public function getTopic(string $key): ?array
    {
        return $this->topics[$key] ?? null;
    }

    public function all(): array
    {
        return [
            'resources' => $this->resources,
            'settingsPages' => $this->settingsPages,
            'topics' => $this->topics,
        ];
    }
}

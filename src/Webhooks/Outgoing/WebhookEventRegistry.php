<?php

namespace Dashed\DashedCore\Webhooks\Outgoing;

/**
 * Welke uitgaande gebeurtenissen er bestaan. Een pakket meldt zijn events aan
 * in packageBooted(), nooit in register(): labels zijn vertaalbaar.
 */
final class WebhookEventRegistry
{
    /** @var array<string, array{group: string, label: string, description: ?string}> */
    private array $events = [];

    public function register(string $group, array $events): void
    {
        foreach ($events as $key => $meta) {
            $this->events[$key] = [
                'group' => $group,
                'label' => (string) ($meta['label'] ?? $key),
                'description' => $meta['description'] ?? null,
            ];
        }
    }

    public function all(): array
    {
        return $this->events;
    }

    public function has(string $event): bool
    {
        return $event === 'ping' || isset($this->events[$event]);
    }

    public function forGroup(string $group): array
    {
        return array_filter($this->events, fn (array $meta) => $meta['group'] === $group);
    }

    public function flush(): void
    {
        $this->events = [];
    }
}

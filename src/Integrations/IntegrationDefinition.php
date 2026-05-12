<?php

namespace Dashed\DashedCore\Integrations;

use InvalidArgumentException;

/**
 * Declarative description of an admin integration card. Provider packages
 * register one of these per integration via `cms()->registerIntegration(...)`
 * during `bootingPackage()`. The IntegrationsDashboard renders one card per
 * registered definition.
 */
final readonly class IntegrationDefinition
{
    public function __construct(
        public string $slug,
        public string $label,
        public string $icon,
        public string $category,
        public ?string $settingsPage,
        /** @var callable */
        public mixed $healthCheck,
        public ?string $docsUrl = null,
        public ?string $permission = null,
        public ?string $package = null,
    ) {
        if (trim($this->slug) === '') {
            throw new InvalidArgumentException('Integration slug cannot be empty.');
        }
        if (! is_callable($this->healthCheck)) {
            throw new InvalidArgumentException('Integration health_check must be callable.');
        }
    }

    public static function fromArray(array $cfg): self
    {
        return new self(
            slug: (string) ($cfg['slug'] ?? ''),
            label: (string) ($cfg['label'] ?? ''),
            icon: (string) ($cfg['icon'] ?? 'heroicon-o-puzzle-piece'),
            category: (string) ($cfg['category'] ?? 'other'),
            settingsPage: $cfg['settings_page'] ?? null,
            healthCheck: $cfg['health_check'] ?? fn () => IntegrationHealth::disabled(),
            docsUrl: $cfg['docs_url'] ?? null,
            permission: $cfg['permission'] ?? null,
            package: $cfg['package'] ?? null,
        );
    }

    public function runHealthCheck(?string $siteId = null): IntegrationHealth
    {
        $result = call_user_func($this->healthCheck, $siteId);

        return $result instanceof IntegrationHealth
            ? $result
            : IntegrationHealth::disabled();
    }
}

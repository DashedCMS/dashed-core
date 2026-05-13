<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Dashed\DashedCore\Enums\IntegrationStatus;
use Dashed\DashedCore\Integrations\IntegrationDefinition;
use Dashed\DashedCore\Integrations\IntegrationHealth;
use Dashed\DashedCore\Integrations\IntegrationHealthRunner;
use Illuminate\Contracts\View\View;

/**
 * Adds an "are we connected?" banner to a Filament settings page that backs
 * a registered integration. The settings page is matched against the
 * IntegrationRegistry by class name — if the current page class matches an
 * integration's `settings_page`, its health check runs and the result is
 * rendered above the form.
 *
 * Usage: add `use ShowsIntegrationHealthBanner;` to a SettingsPage. The
 * shared `dashed-core::settings.pages.default-settings` view auto-detects
 * the trait method and renders the banner.
 */
trait ShowsIntegrationHealthBanner
{
    /**
     * Look up the integration registered against this settings page (by class
     * name) and render a status banner. Returns null when no integration is
     * registered for this page — the view then renders nothing.
     */
    public function renderIntegrationHealthBanner(): ?View
    {
        $definition = $this->resolveIntegrationDefinition();
        if ($definition === null) {
            return null;
        }

        try {
            $runner = app(IntegrationHealthRunner::class);
            $health = $runner->run($definition);
        } catch (\Throwable $e) {
            $health = IntegrationHealth::failing($e->getMessage());
        }

        return view('dashed-core::components.integration-status-banner', [
            'definition' => $definition,
            'health' => $health,
        ]);
    }

    protected function resolveIntegrationDefinition(): ?IntegrationDefinition
    {
        $registry = cms()->integrationRegistry();
        foreach ($registry->all() as $definition) {
            if ($definition->settingsPage === static::class) {
                return $definition;
            }
        }

        return null;
    }
}

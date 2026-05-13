<?php

namespace Dashed\DashedCore\Filament\Pages;

use BackedEnum;
use UnitEnum;
use Filament\Pages\Page;
use Dashed\DashedCore\Integrations\IntegrationHealthRunner;

/**
 * Centrale admin-pagina die alle geregistreerde integraties met
 * status-stip toont. Provider-pakketten registreren zichzelf via
 * `cms()->registerIntegration(...)` in `bootingPackage()`; deze pagina
 * leest de `IntegrationRegistry` en draait elke `healthCheck` door de
 * 5-min-gecachte `IntegrationHealthRunner`.
 */
class IntegrationsDashboard extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 99000;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Integraties';

    protected static ?string $title = 'Integraties';

    protected static ?string $slug = 'integrations';

    protected string $view = 'dashed-core::pages.integrations-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $registry = cms()->integrationRegistry();
        $defs = $registry->all();

        // No integrations registered yet — only super-admin sees the empty
        // page so we don't surface a permission-less blank screen to other
        // roles.
        if ($defs === []) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
        }

        foreach ($defs as $def) {
            if ($def->permission === null) {
                return true;
            }
            if (method_exists($user, 'can') && $user->can($def->permission)) {
                return true;
            }
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getIntegrationsProperty(): array
    {
        $runner = app(IntegrationHealthRunner::class);
        $byCategory = cms()->integrationRegistry()->byCategory();

        $out = [];
        foreach ($byCategory as $category => $defs) {
            $rows = [];
            foreach ($defs as $def) {
                $rows[] = [
                    'definition' => $def,
                    'health' => $runner->run($def),
                ];
            }
            $out[$category] = $rows;
        }

        return $out;
    }

    public function refreshIntegration(string $slug): void
    {
        app(IntegrationHealthRunner::class)->forget($slug);
        $this->dispatch('$refresh');
    }
}

<?php

namespace Dashed\DashedCore\Dashboard;

class DashboardWidgetRegistry
{
    /** @return array<string, array{id:string,class:string,label:string,width:int|string,sort:int}> */
    public function all(): array
    {
        $items = [];

        // 1. Expliciet geregistreerd via cms()->builder('dashboardWidgets', [...]).
        foreach ((array) cms()->builder('dashboardWidgets') as $id => $config) {
            $class = $config['widget'] ?? null;
            if (! $class || ! class_exists($class)) {
                continue;
            }
            if (isset($config['permission']) && is_callable($config['permission']) && ! $config['permission']()) {
                continue;
            }
            if (! $this->widgetIsViewable($class)) {
                continue;
            }
            $items[(string) $id] = [
                'id' => (string) $id,
                'class' => $class,
                'label' => (string) ($config['label'] ?? class_basename($class)),
                'width' => DashboardLayout::clampWidth($config['width'] ?? 'full'),
                'sort' => (int) ($config['sort'] ?? 100),
            ];
        }

        // 2. Fallback: door Filament ontdekte dashboard-widgets die niet expliciet
        //    geregistreerd zijn. Best-effort: faalt stil als het panel niet beschikbaar is.
        foreach ($this->discoveredWidgetClasses() as $class) {
            if (! class_exists($class)) {
                continue;
            }
            if (! $this->widgetIsViewable($class)) {
                continue;
            }
            // Sla over als deze klasse al expliciet geregistreerd is.
            foreach ($items as $existing) {
                if ($existing['class'] === $class) {
                    continue 2;
                }
            }
            $id = $class; // FQCN als stabiele id voor niet-geregistreerde widgets
            $items[$id] = [
                'id' => $id,
                'class' => $class,
                'label' => \Illuminate\Support\Str::headline(class_basename($class)),
                'width' => 'full',
                'sort' => 100,
            ];
        }

        uasort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return $items;
    }

    public function get(string $id): ?array
    {
        return $this->all()[$id] ?? null;
    }

    protected function widgetIsViewable(string $class): bool
    {
        if (! method_exists($class, 'canView')) {
            return true;
        }

        return rescue(fn () => (bool) $class::canView(), true, false);
    }

    /** @return array<int, class-string> */
    protected function discoveredWidgetClasses(): array
    {
        // Only discover via Filament when a panel is actively serving a request.
        // Without a current panel (e.g. console, tests, artisan) we skip discovery
        // to avoid pulling in all registered widgets unintentionally.
        return rescue(function () {
            if (! \Filament\Facades\Filament::getCurrentPanel()) {
                return [];
            }

            $widgets = \Filament\Facades\Filament::getWidgets();

            return collect($widgets)
                ->map(fn ($w) => is_string($w) ? $w : (is_object($w) ? $w::class : null))
                ->filter()
                ->reject(fn ($c) => $c === \Dashed\DashedCore\Filament\Widgets\DashboardGrid::class)
                ->values()
                ->all();
        }, [], false);
    }
}

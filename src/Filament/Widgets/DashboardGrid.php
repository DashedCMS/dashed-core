<?php

namespace Dashed\DashedCore\Filament\Widgets;

use Filament\Widgets\Widget;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Dashboard\DashboardLayout;

class DashboardGrid extends Widget
{
    protected string $view = 'dashed-core::filament.widgets.dashboard-grid';

    protected int | string | array $columnSpan = 'full';

    public bool $editing = false;

    /** @var array<int, array{id:string,class:string,label:string,visible:bool,width:int|string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->loadItems();
    }

    protected function loadItems(): void
    {
        $this->items = app(DashboardLayout::class)->resolved($this->siteId());
    }

    protected function siteId(): string
    {
        return Sites::getActive();
    }

    public function canEdit(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'superadmin'], true);
    }

    public function toggleEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->editing = ! $this->editing;
    }

    public function toggleWidget(string $id): void
    {
        abort_unless($this->canEdit(), 403);
        foreach ($this->items as &$item) {
            if ($item['id'] === $id) {
                $item['visible'] = ! $item['visible'];
            }
        }
        $this->persist();
    }

    public function setWidth(string $id, int|string $width): void
    {
        abort_unless($this->canEdit(), 403);
        foreach ($this->items as &$item) {
            if ($item['id'] === $id) {
                $item['width'] = DashboardLayout::clampWidth($width);
            }
        }
        $this->persist();
    }

    /** @param array<int, string> $orderedIds */
    public function reorder(array $orderedIds): void
    {
        abort_unless($this->canEdit(), 403);
        $byId = collect($this->items)->keyBy('id');
        $new = [];
        foreach ($orderedIds as $id) {
            if ($byId->has($id)) {
                $new[] = $byId->get($id);
                $byId->forget($id);
            }
        }
        // Niet-genoemde items behouden (achteraan).
        foreach ($byId as $item) {
            $new[] = $item;
        }
        $this->items = $new;
        $this->persist();
    }

    public function resetLayout(): void
    {
        abort_unless($this->canEdit(), 403);
        app(DashboardLayout::class)->reset($this->siteId());
        $this->loadItems();
    }

    protected function persist(): void
    {
        app(DashboardLayout::class)->save(
            $this->siteId(),
            collect($this->items)->map(fn ($i) => [
                'id' => $i['id'],
                'visible' => $i['visible'],
                'width' => $i['width'],
            ])->all(),
        );
        $this->loadItems();
    }
}

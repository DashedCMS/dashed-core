<?php

namespace Dashed\DashedCore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Dashed\DashedCore\Classes\NavigationUsage;

class NavigationTopPinsTest extends TestCase
{
    private const ANCHORS = ['/dashed', '/dashed/mijn-taken'];

    public function test_top_pinned_items_move_below_dashboard_and_my_tasks_in_pin_order(): void
    {
        $groups = [
            'N;' => NavigationGroup::make()->items([
                $this->item('Mijn taken', '/dashed/mijn-taken'),
                $this->item('Dashboard', '/dashed'),
                $this->item('Los', '/dashed/los'),
            ]),
            's:4:"Shop";' => NavigationGroup::make('Shop')->items([
                $this->item('Bestellingen', '/dashed/orders'),
                $this->item('Producten', '/dashed/products'),
            ]),
            's:7:"Content";' => NavigationGroup::make('Content')->items([
                $this->item('Pagina\'s', '/dashed/pages'),
            ]),
        ];

        $result = NavigationUsage::moveTopPins($groups, ['/dashed/pages' => 3, '/dashed/orders' => 7], self::ANCHORS);

        $this->assertSame(
            ['/dashed/mijn-taken', '/dashed', '/dashed/pages', '/dashed/orders', '/dashed/los'],
            $this->keys($result['N;']),
        );
        $this->assertSame(['/dashed/products'], $this->keys($result['s:4:"Shop";']));
        // Een kopje dat leeg raakt verdwijnt uit het menu.
        $this->assertArrayNotHasKey('s:7:"Content";', $result);
    }

    public function test_an_ungrouped_section_is_created_at_the_front_when_there_is_none(): void
    {
        $groups = [
            's:4:"Shop";' => NavigationGroup::make('Shop')->items([
                $this->item('Bestellingen', '/dashed/orders'),
                $this->item('Producten', '/dashed/products'),
            ]),
        ];

        $result = NavigationUsage::moveTopPins($groups, ['/dashed/products' => 0], self::ANCHORS);

        $this->assertSame(['N;', 's:4:"Shop";'], array_keys($result));
        $this->assertSame(['/dashed/products'], $this->keys($result['N;']));
        $this->assertSame(['/dashed/orders'], $this->keys($result['s:4:"Shop";']));
    }

    public function test_anchors_and_unknown_keys_stay_where_they_are(): void
    {
        $groups = [
            'N;' => NavigationGroup::make()->items([
                $this->item('Dashboard', '/dashed'),
            ]),
            's:4:"Shop";' => NavigationGroup::make('Shop')->items([
                $this->item('Bestellingen', '/dashed/orders'),
            ]),
        ];

        $result = NavigationUsage::moveTopPins($groups, ['/dashed' => 0, '/dashed/weg' => 1], self::ANCHORS);

        $this->assertSame(['/dashed'], $this->keys($result['N;']));
        $this->assertSame(['/dashed/orders'], $this->keys($result['s:4:"Shop";']));
    }

    public function test_top_pins_are_split_from_group_pins_by_their_prefix(): void
    {
        $pins = ['/dashed/orders' => 0, NavigationUsage::TOP_PREFIX . '/dashed/pages' => 1, NavigationUsage::TOP_PREFIX . '/dashed/orders' => 2];

        $this->assertSame(['/dashed/pages' => 1, '/dashed/orders' => 2], NavigationUsage::topPins($pins));
        $this->assertSame([], NavigationUsage::moveTopPins([], []));
    }

    private function item(string $label, string $url): NavigationItem
    {
        return NavigationItem::make($label)->url($url);
    }

    /**
     * @return list<string|null>
     */
    private function keys(NavigationGroup $group): array
    {
        return array_map(
            fn (NavigationItem $item) => NavigationUsage::keyFor($item->getUrl()),
            array_values(collect($group->getItems())->all()),
        );
    }
}

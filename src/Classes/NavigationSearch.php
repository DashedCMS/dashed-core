<?php

namespace Dashed\DashedCore\Classes;

use Throwable;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Dashed\DashedCore\Filament\Pages\Settings\SettingsPage;

/**
 * De lijst achter de zoekpopup van het menu ("/" of Ctrl/Cmd+K): elk
 * menu-item dat de ingelogde gebruiker ziet, plus elk instellingenscherm dat
 * hij mag openen. Die laatste staan niet in het menu maar alleen als kaart op
 * de instellingenpagina, en juist die zijn lastig terug te vinden.
 *
 * De lijst wordt per pagina aan de serverkant opgebouwd en in de pagina gezet;
 * filteren gebeurt in de browser. Rechten zitten dus al in de lijst: Filaments
 * navigatie laat alleen zien wat canAccess() toestaat, en de instellingen komen
 * uit SettingsPage::visibleSettingPages().
 *
 * Elk item heeft een gewicht: pakketten melden hun belangrijkste pagina's aan
 * met cms()->builder('navigationSearchPriority', [Klasse::class => gewicht]),
 * zodat bijvoorbeeld Bestellingen boven Openstaande bestellingen staat. Wat
 * een gebruiker zelf vaak of recent opent, houdt de popup in de browser bij.
 */
class NavigationSearch
{
    /**
     * @return list<array{label: string, group: string, url: string, kind: string, weight: int}>
     */
    public static function items(): array
    {
        $items = [];
        $weights = self::priorityWeights();

        $panel = Filament::getCurrentOrDefaultPanel();

        foreach ($panel?->getNavigation() ?? [] as $group) {
            $groupLabel = (string) ($group->getLabel() ?? '');

            foreach ($group->getItems() as $item) {
                self::addItem($items, $item, $groupLabel);

                foreach ($item->getChildItems() as $child) {
                    self::addItem($items, $child, $item->getLabel());
                }
            }
        }

        foreach (SettingsPage::visibleSettingPages() as $page) {
            try {
                $url = $page['page']::getUrl();
            } catch (Throwable) {
                continue;
            }

            $items[$url] ??= [
                'label' => (string) $page['name'],
                'group' => __('Instellingen'),
                'url' => $url,
                'kind' => 'settings',
                'weight' => 0,
            ];
        }

        foreach ($items as $url => $item) {
            $items[$url]['weight'] = $weights[$url] ?? $item['weight'];
        }

        return array_values($items);
    }

    /**
     * Gewicht per URL, uit de aangemelde klassen. Een resource telt met zijn
     * lijstpagina, een pagina met zichzelf. Een klasse zonder route (pakket
     * niet op dit paneel) valt stil weg.
     *
     * @return array<string, int>
     */
    public static function priorityWeights(): array
    {
        $weights = [];

        foreach (cms()->builder('navigationSearchPriority') as $class => $weight) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            try {
                $url = is_subclass_of($class, \Filament\Resources\Resource::class)
                    ? $class::getUrl('index')
                    : $class::getUrl();
            } catch (Throwable) {
                continue;
            }

            $weights[$url] = (int) $weight;
        }

        return $weights;
    }

    /**
     * @param  array<string, array{label: string, group: string, url: string, kind: string, weight: int}>  $items
     */
    private static function addItem(array &$items, NavigationItem $item, string $group): void
    {
        if (! $item->isVisible() || ! ($url = $item->getUrl())) {
            return;
        }

        // Op URL ontdubbelen: een pagina die zowel in het menu als als
        // instellingenkaart bestaat, hoort er één keer in te staan, met de
        // naam uit het menu.
        $items[$url] ??= [
            'label' => $item->getLabel(),
            'group' => $group,
            'url' => $url,
            'kind' => 'menu',
            'weight' => 0,
        ];
    }
}

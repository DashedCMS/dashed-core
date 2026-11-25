<?php

namespace Dashed\DashedCore\Classes;

use Throwable;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Sorteert de items binnen elk menukopje van het paneel per beheerder: eerst
 * wat hij zelf vastpinde (in de volgorde van pinnen), daarna op hoe vaak hij
 * het de laatste WINDOW_DAYS dagen opende, en bij gelijkspel of nooit bezocht
 * de vaste volgorde van de pakketten. De kopjes zelf blijven staan.
 *
 * Een menu-item heet hier naar zijn pad (keyFor()): stabiel over talen heen,
 * waar het label dat niet is. Een bezoek telt voor het menu-item met het
 * langste pad dat het verzoek omvat, zodat een bewerkpagina meetelt voor zijn
 * lijst. De startpagina van het paneel telt alleen bij een exacte treffer,
 * anders zou elke pagina ook voor het dashboard tellen.
 *
 * Daarnaast kan een beheerder een item helemaal bovenaan pinnen (zie
 * moveTopPins()): dat item verlaat zijn kopje en staat direct onder Dashboard
 * en Mijn taken, in de volgorde van pinnen. Zo'n pin staat in dezelfde tabel,
 * met TOP_PREFIX voor het pad; een pad begint altijd met een slash, dus de
 * twee soorten pins lopen nooit door elkaar.
 *
 * Alles hier loopt via rescue(): een ontbrekende tabel midden in een uitrol
 * mag het menu niet breken, dan blijft gewoon de vaste volgorde staan.
 */
class NavigationUsage
{
    public const WINDOW_DAYS = 90;

    /** Voorvoegsel in item_key voor een pin helemaal bovenaan. */
    public const TOP_PREFIX = 'top:';

    private const MEMO = 'dashed.navigation_usage';

    public static function keyFor(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        return '/' . trim($path, '/');
    }

    public static function enabledFor(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        // null als de kolom (nog) niet bestaat: dan geldt de standaard, aan.
        return $user->getAttribute('navigation_sort_by_usage') !== false;
    }

    /**
     * @param  array<NavigationGroup>  $groups
     * @return array<NavigationGroup>
     */
    public static function sort(array $groups): array
    {
        $user = auth()->user();

        if (! self::enabledFor($user)) {
            return $groups;
        }

        return rescue(function () use ($groups, $user) {
            ['pins' => $pins, 'visits' => $visits] = self::dataFor($user);

            if ($pins === [] && $visits === []) {
                return $groups;
            }

            foreach ($groups as $group) {
                $items = collect($group->getItems())->values();

                if ($items->count() < 2) {
                    continue;
                }

                $group->items(self::sortItems($items, $pins, $visits));
            }

            return self::moveTopPins($groups, self::topPins($pins), self::anchorKeys());
        }, $groups, report: false);
    }

    /**
     * Haalt de items die helemaal bovenaan vastgepind zijn uit hun kopje en
     * zet ze in de groep zonder kopje, direct na de ankers (Dashboard en Mijn
     * taken), in de volgorde van pinnen. Een kopje dat daardoor leeg raakt
     * verdwijnt; bestaat er nog geen groep zonder kopje, dan komt die vooraan.
     *
     * @param  array<NavigationGroup>  $groups
     * @param  array<string, int>  $topPins  sleutel => volgorde van pinnen
     * @param  list<string>  $anchorKeys  sleutels die erboven blijven staan
     * @return array<NavigationGroup>
     */
    public static function moveTopPins(array $groups, array $topPins, array $anchorKeys = []): array
    {
        if ($topPins === []) {
            return $groups;
        }

        $moved = [];

        foreach ($groups as $group) {
            $items = collect($group->getItems())->values();

            $keep = $items->reject(function (NavigationItem $item) use ($topPins, $anchorKeys, &$moved): bool {
                $key = self::keyFor($item->getUrl());

                if ($key === null || ! isset($topPins[$key]) || in_array($key, $anchorKeys, true) || isset($moved[$key])) {
                    return false;
                }

                $moved[$key] = $item;

                return true;
            });

            if ($keep->count() !== $items->count()) {
                $group->items($keep->values()->all());
            }
        }

        if ($moved === []) {
            return $groups;
        }

        uksort($moved, fn (string $a, string $b): int => $topPins[$a] <=> $topPins[$b]);

        $ungrouped = null;

        foreach ($groups as $group) {
            if (blank($group->getLabel())) {
                $ungrouped = $group;

                break;
            }
        }

        if ($ungrouped === null) {
            $ungrouped = NavigationGroup::make();
            $groups = array_merge([serialize(null) => $ungrouped], $groups);
        }

        [$anchors, $rest] = collect($ungrouped->getItems())
            ->values()
            ->partition(fn (NavigationItem $item): bool => in_array(self::keyFor($item->getUrl()), $anchorKeys, true));

        $ungrouped->items([...$anchors->all(), ...array_values($moved), ...$rest->all()]);

        return array_filter($groups, fn (NavigationGroup $group): bool => filled($group->getItems()));
    }

    /**
     * De paden die altijd boven de items helemaal bovenaan blijven staan: het
     * dashboard (de startpagina van het paneel) en Mijn taken.
     *
     * @return list<string>
     */
    public static function anchorKeys(): array
    {
        $home = '/' . trim((string) Filament::getCurrentOrDefaultPanel()?->getPath(), '/');
        $tasks = rescue(fn () => \Dashed\DashedCore\Filament\Pages\MyTasksPage::getSlug(), 'mijn-taken', report: false);

        return [$home, rtrim($home, '/') . '/' . trim((string) $tasks, '/')];
    }

    /**
     * Alleen de pins helemaal bovenaan, zonder voorvoegsel.
     *
     * @param  array<string, int>  $pins
     * @return array<string, int>
     */
    public static function topPins(array $pins): array
    {
        $top = [];

        foreach ($pins as $key => $order) {
            if (str_starts_with((string) $key, self::TOP_PREFIX)) {
                $top[substr((string) $key, strlen(self::TOP_PREFIX))] = $order;
            }
        }

        return $top;
    }

    /**
     * @param  Collection<int, NavigationItem>  $items
     * @param  array<string, int>  $pins  sleutel => volgorde van pinnen
     * @param  array<string, int>  $visits  sleutel => aantal bezoeken
     * @return array<NavigationItem>
     */
    public static function sortItems(Collection $items, array $pins, array $visits): array
    {
        return $items
            ->map(function (NavigationItem $item, int $index) use ($pins, $visits): array {
                $key = self::keyFor($item->getUrl());
                $count = $visits[$key] ?? 0;

                foreach ($item->getChildItems() as $child) {
                    $count += $visits[self::keyFor($child->getUrl())] ?? 0;
                }

                return [
                    'item' => $item,
                    'pin' => $key !== null && isset($pins[$key]) ? $pins[$key] : null,
                    'visits' => $count,
                    'index' => $index,
                ];
            })
            ->sort(function (array $a, array $b): int {
                if ($a['pin'] !== null || $b['pin'] !== null) {
                    if ($a['pin'] === null) {
                        return 1;
                    }
                    if ($b['pin'] === null) {
                        return -1;
                    }

                    return $a['pin'] <=> $b['pin'];
                }

                return [$b['visits'], $a['index']] <=> [$a['visits'], $b['index']];
            })
            ->pluck('item')
            ->values()
            ->all();
    }

    /**
     * Telt een paginalading mee voor het menu-item waar hij onder valt.
     */
    public static function record(Authenticatable $user, Request $request): void
    {
        rescue(function () use ($user, $request): void {
            $key = self::matchItem('/' . trim($request->path(), '/'));

            if ($key === null) {
                return;
            }

            DB::table('dashed__navigation_visits')->upsert(
                [[
                    'user_id' => $user->getAuthIdentifier(),
                    'item_key' => $key,
                    'date' => now()->toDateString(),
                    'visits' => 1,
                ]],
                ['user_id', 'item_key', 'date'],
                ['visits' => DB::raw('dashed__navigation_visits.visits + 1')],
            );

            self::forgetMemo();
        }, report: false);
    }

    public static function matchItem(string $path): ?string
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $home = '/' . trim((string) $panel?->getPath(), '/');
        $best = null;

        foreach (self::itemKeys() as $key) {
            $matches = $key === $path
                || ($key !== $home && str_starts_with($path, rtrim($key, '/') . '/'));

            if ($matches && ($best === null || strlen($key) > strlen($best))) {
                $best = $key;
            }
        }

        return $best;
    }

    /**
     * Elk pad in het menu van de ingelogde gebruiker, kinderen inbegrepen.
     * Daarmee wordt ook gecontroleerd wat er vastgepind mag worden.
     *
     * @return list<string>
     */
    public static function itemKeys(): array
    {
        $keys = [];

        foreach (Filament::getCurrentOrDefaultPanel()?->getNavigation() ?? [] as $group) {
            foreach ($group->getItems() as $item) {
                $keys[] = self::keyFor($item->getUrl());

                foreach ($item->getChildItems() as $child) {
                    $keys[] = self::keyFor($child->getUrl());
                }
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * Pint vast of maakt los; geeft terug of het item nu vastgepind is. Met
     * $top gaat het om de pin helemaal bovenaan, anders om die in het kopje.
     */
    public static function togglePin(Authenticatable $user, string $key, bool $top = false): bool
    {
        if ($top) {
            $key = self::TOP_PREFIX . $key;
        }

        $query = DB::table('dashed__navigation_pins')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('item_key', $key);

        self::forgetMemo();

        if ($query->exists()) {
            $query->delete();

            return false;
        }

        DB::table('dashed__navigation_pins')->insert([
            'user_id' => $user->getAuthIdentifier(),
            'item_key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Wat de gebruiker in een kopje vastpinde.
     *
     * @return list<string>
     */
    public static function pinnedKeys(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        return rescue(fn () => array_values(array_filter(
            array_map('strval', array_keys(self::dataFor($user)['pins'])),
            fn (string $key): bool => ! str_starts_with($key, self::TOP_PREFIX),
        )), [], report: false);
    }

    /**
     * Wat de gebruiker helemaal bovenaan vastpinde, in de volgorde van pinnen.
     *
     * @return list<string>
     */
    public static function topPinnedKeys(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        return rescue(fn () => array_keys(self::topPins(self::dataFor($user)['pins'])), [], report: false);
    }

    /**
     * Eén keer per verzoek: Filament bouwt het menu meerdere keren op (zijbalk,
     * zoekpopup), en elke keer dezelfde twee query's is zonde.
     *
     * @return array{pins: array<string, int>, visits: array<string, int>}
     */
    private static function dataFor(Authenticatable $user): array
    {
        $request = request();
        $memoKey = self::MEMO . '.' . $user->getAuthIdentifier();

        if ($request->attributes->has($memoKey)) {
            return $request->attributes->get($memoKey);
        }

        $pins = DB::table('dashed__navigation_pins')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('item_key')
            ->flip()
            ->all();

        $visits = DB::table('dashed__navigation_visits')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('date', '>=', now()->subDays(self::WINDOW_DAYS)->toDateString())
            ->groupBy('item_key')
            ->selectRaw('item_key, SUM(visits) as total')
            ->pluck('total', 'item_key')
            ->map(fn ($total) => (int) $total)
            ->all();

        $data = ['pins' => $pins, 'visits' => $visits];
        $request->attributes->set($memoKey, $data);

        return $data;
    }

    private static function forgetMemo(): void
    {
        try {
            foreach (array_keys(request()->attributes->all()) as $key) {
                if (str_starts_with((string) $key, self::MEMO . '.')) {
                    request()->attributes->remove($key);
                }
            }
        } catch (Throwable) {
        }
    }
}

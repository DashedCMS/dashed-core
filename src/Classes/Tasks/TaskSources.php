<?php

namespace Dashed\DashedCore\Classes\Tasks;

/**
 * Register van takenbronnen voor "Mijn taken". Projecten en pakketten
 * registreren hun bronnen in een serviceprovider:
 *
 *     TaskSources::register(TaskSource::resource(OrderReturnResource::class, group: 'Shop'));
 *
 * Elke bron wordt los geteld; een bron die faalt of 0 geeft valt weg.
 */
class TaskSources
{
    /** @var array<string, TaskSource> */
    protected static array $sources = [];

    public static function register(TaskSource ...$sources): void
    {
        foreach ($sources as $source) {
            static::$sources[$source->key] = $source;
        }
    }

    public static function forget(string $key): void
    {
        unset(static::$sources[$key]);
    }

    public static function flush(): void
    {
        static::$sources = [];
    }

    /** @return array<string, TaskSource> */
    public static function all(): array
    {
        return static::$sources;
    }

    /**
     * De bronnen met open werk die de ingelogde gebruiker mag zien, gesorteerd
     * op groep (in volgorde van registratie) en daarbinnen op sort.
     *
     * @return array<int, array{key: string, label: string, count: int, url: ?string, icon: mixed, group: ?string}>
     */
    public static function open(): array
    {
        $groupOrder = [];
        $tiles = [];

        foreach (static::$sources as $source) {
            $groupOrder[$source->group ?? ''] ??= count($groupOrder);

            $tile = rescue(function () use ($source) {
                if ($source->visible && ! ($source->visible)()) {
                    return null;
                }

                $count = (int) ($source->count)();

                if ($count <= 0) {
                    return null;
                }

                return [
                    'key' => $source->key,
                    'label' => (string) ($source->label)(),
                    'count' => $count,
                    'url' => rescue(fn () => ($source->url)(), null, report: false),
                    'icon' => $source->icon ? ($source->icon)() : null,
                    'group' => $source->group,
                    'sort' => $source->sort,
                ];
            }, null, report: false);

            if ($tile) {
                $tiles[] = $tile;
            }
        }

        usort($tiles, fn (array $a, array $b) => [$groupOrder[$a['group'] ?? ''], $a['sort']] <=> [$groupOrder[$b['group'] ?? ''], $b['sort']]);

        return $tiles;
    }

    public static function openCount(): int
    {
        return array_sum(array_column(static::open(), 'count'));
    }
}

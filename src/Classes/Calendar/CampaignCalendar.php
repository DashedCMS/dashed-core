<?php

namespace Dashed\DashedCore\Classes\Calendar;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Trash\Trash;

/**
 * De campagnekalender: kortingscodes, popups, nieuwsbrieven en ingeplande
 * pagina's op één maandweergave per site. Elk pakket meldt een bron aan met
 * een closure die de events in een periode oplevert; de kalender zelf kent
 * geen enkel pakket. Een bron draait binnen rescue(), zodat een kapotte bron
 * de rest van de kalender niet meeneemt.
 */
class CampaignCalendar
{
    /** @var array<string, array{label: string, color: string, events: Closure}> */
    protected static array $sources = [];

    /**
     * @param  Closure(Carbon $from, Carbon $to, ?string $siteId): iterable<CalendarEvent>  $events
     */
    public static function source(string $key, string $label, string $color, Closure $events): void
    {
        static::$sources[$key] = ['label' => $label, 'color' => $color, 'events' => $events];
    }

    /**
     * @return array<string, array{label: string, color: string, events: Closure}>
     */
    public static function sources(): array
    {
        return static::$sources;
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    public static function events(Carbon $from, Carbon $to, ?string $siteId = null): Collection
    {
        return collect(static::$sources)
            ->flatMap(fn (array $source) => rescue(fn () => collect(($source['events'])($from, $to, $siteId))->all(), [], report: true))
            ->filter(fn ($event) => $event instanceof CalendarEvent)
            ->filter(fn (CalendarEvent $event) => $event->start->lte($to) && $event->endOr($to)->gte($from))
            ->sortBy(fn (CalendarEvent $event) => $event->start->timestamp)
            ->values();
    }

    /**
     * Paren events die met dezelfde overlapsleutel tegelijk lopen.
     *
     * @param  Collection<int, CalendarEvent>  $events
     * @return array<int, array{a: CalendarEvent, b: CalendarEvent, key: string, from: Carbon, to: Carbon}>
     */
    public static function conflicts(Collection $events, Carbon $horizon): array
    {
        $list = $events->filter(fn (CalendarEvent $event) => $event->overlapKeys !== [])->values();
        $conflicts = [];

        for ($i = 0; $i < $list->count(); $i++) {
            for ($j = $i + 1; $j < $list->count(); $j++) {
                $a = $list[$i];
                $b = $list[$j];
                $key = $a->sharesKeyWith($b);

                if ($key === null || ! $a->overlapsInTime($b, $horizon)) {
                    continue;
                }

                $conflicts[] = [
                    'a' => $a,
                    'b' => $b,
                    'key' => $key,
                    'from' => $a->start->max($b->start),
                    'to' => $a->endOr($horizon)->min($b->endOr($horizon)),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Bron voor alles wat via IsVisitable een start- of einddatum heeft
     * (pagina's, artikelen): "ingepland" is een model dat pas later online
     * komt of op een datum offline gaat. Per soort hooguit honderd.
     */
    public static function registerScheduledContent(): void
    {
        static::source('scheduled', __('Ingepland'), '#6366f1', function (Carbon $from, Carbon $to, ?string $siteId) {
            $events = [];

            foreach (cms()->builder('routeModels') as $routeModel) {
                $class = $routeModel['class'] ?? null;
                if (! $class || ! class_exists($class) || ! \Illuminate\Support\Facades\Schema::hasColumn((new $class())->getTable(), 'start_date')) {
                    continue;
                }

                $class::query()
                    ->where(fn ($q) => $q->whereBetween('start_date', [$from, $to])->orWhereBetween('end_date', [$from, $to])->orWhere(fn ($q) => $q->where('start_date', '<', $from)->where('end_date', '>', $to)))
                    ->when($siteId && \Illuminate\Support\Facades\Schema::hasColumn((new $class())->getTable(), 'site_ids'), fn ($q) => $q->where('site_ids', 'LIKE', '%"'.$siteId.'"%'))
                    ->limit(100)
                    ->get()
                    ->each(function ($model) use (&$events, $routeModel) {
                        $events[] = new CalendarEvent(
                            source: 'scheduled',
                            title: ($routeModel['name'] ?? '').': '.Trash::title($model),
                            start: Carbon::parse($model->start_date ?? $model->end_date),
                            end: $model->end_date ? Carbon::parse($model->end_date) : null,
                            url: Trash::editUrl($model),
                        );
                    });
            }

            return $events;
        });
    }

    public static function flush(): void
    {
        static::$sources = [];
    }
}

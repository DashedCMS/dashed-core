<?php

namespace Dashed\DashedCore\Classes\Calendar;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Trash\Trash;
use Dashed\DashedCore\Classes\SchemaColumns;

/**
 * De contentkalender: wat er gepubliceerd is of ingepland staat (pagina's,
 * artikelen, socialposts) naast de bronnen van de campagnekalender
 * (kortingscodes, popups, nieuwsbrieven). Een pakket meldt een eigen bron aan
 * met cms()->builder('contentCalendarSources', [sleutel => ['label' => ...,
 * 'color' => ..., 'events' => Closure(Carbon $van, Carbon $tot, ?string
 * $siteId): iterable<CalendarEvent>]]), in bootingPackage(). De bron
 * 'scheduled' van de campagnekalender blijft hier weg: die toont hetzelfde
 * als de pagina- en artikelbronnen, alleen minder precies.
 */
class ContentCalendar
{
    public const BUILDER = 'contentCalendarSources';

    /** Campagnebronnen die de contentkalender niet overneemt. */
    public const SKIPPED_CAMPAIGN_SOURCES = ['scheduled'];

    /**
     * @return array<string, array{label: string, color: string, events: Closure}>
     */
    public static function sources(): array
    {
        $campaign = collect(CampaignCalendar::sources())->except(self::SKIPPED_CAMPAIGN_SOURCES)->all();

        $own = collect(cms()->builder(self::BUILDER))
            ->filter(fn ($source) => is_array($source) && ($source['events'] ?? null) instanceof Closure)
            ->all();

        return array_merge($campaign, $own);
    }

    /**
     * @param  array<int, string>|null  $only  bronsleutels; null is alles
     * @return Collection<int, CalendarEvent>
     */
    public static function events(Carbon $from, Carbon $to, ?string $siteId = null, ?array $only = null): Collection
    {
        return collect(static::sources())
            ->when($only !== null, fn (Collection $sources) => $sources->only($only))
            ->flatMap(fn (array $source) => rescue(fn () => collect(($source['events'])($from, $to, $siteId))->all(), [], report: true))
            ->filter(fn ($event) => $event instanceof CalendarEvent)
            ->filter(fn (CalendarEvent $event) => $event->start->lte($to) && $event->endOr($to)->gte($from))
            ->sortBy(fn (CalendarEvent $event) => $event->start->timestamp)
            ->values();
    }

    /**
     * Een bron voor een model met IsVisitable: op de publicatiedag
     * (start_date, anders created_at) één regel, met "ingepland" als die dag
     * nog moet komen. Een einddatum geeft een tweede regel "gaat offline".
     * Per bron hooguit tweehonderd regels per periode.
     *
     * @return array{label: string, color: string, events: Closure}
     */
    public static function visitableSource(string $key, string $class, string $label, string $color): array
    {
        return [
            'label' => $label,
            'color' => $color,
            'events' => function (Carbon $from, Carbon $to, ?string $siteId) use ($key, $class, $label) {
                if (! class_exists($class)) {
                    return [];
                }

                $table = (new $class())->getTable();
                $hasStart = SchemaColumns::has($table, 'start_date');
                $hasEnd = SchemaColumns::has($table, 'end_date');
                $events = [];

                $class::query()
                    ->where(function ($q) use ($from, $to, $hasStart, $hasEnd) {
                        if ($hasStart) {
                            $q->whereBetween('start_date', [$from, $to])
                                ->orWhere(fn ($q) => $q->whereNull('start_date')->whereBetween('created_at', [$from, $to]));
                        } else {
                            $q->whereBetween('created_at', [$from, $to]);
                        }
                        if ($hasEnd) {
                            $q->orWhereBetween('end_date', [$from, $to]);
                        }
                    })
                    ->when($siteId && SchemaColumns::has($table, 'site_ids'), fn ($q) => $q->where('site_ids', 'LIKE', '%"'.$siteId.'"%'))
                    ->limit(200)
                    ->get()
                    ->each(function ($model) use (&$events, $key, $label, $from, $to) {
                        $title = Trash::title($model);
                        $url = Trash::editUrl($model);
                        $published = Carbon::parse($model->start_date ?? $model->created_at);

                        if ($published->betweenIncluded($from, $to)) {
                            $events[] = new CalendarEvent(
                                source: $key,
                                title: $published->isFuture()
                                    ? __(':soort: :titel (ingepland)', ['soort' => $label, 'titel' => $title])
                                    : __(':soort: :titel', ['soort' => $label, 'titel' => $title]),
                                start: $published,
                                end: $published->copy()->endOfDay(),
                                url: $url,
                            );
                        }

                        if ($model->end_date && Carbon::parse($model->end_date)->betweenIncluded($from, $to)) {
                            $end = Carbon::parse($model->end_date);
                            $events[] = new CalendarEvent(
                                source: $key,
                                title: __(':soort: :titel (gaat offline)', ['soort' => $label, 'titel' => $title]),
                                start: $end,
                                end: $end->copy()->endOfDay(),
                                url: $url,
                            );
                        }
                    });

                return $events;
            },
        ];
    }
}

<?php

namespace Dashed\DashedCore\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Illuminate\Support\Carbon;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Calendar\CalendarEvent;
use Dashed\DashedCore\Classes\Calendar\ContentCalendar;

/**
 * Contentkalender: per maand wat er gepubliceerd is en ingepland staat
 * (pagina's, artikelen, socialposts) naast de campagnes, met een filter per
 * bron. Een klik op een regel opent het record. Zie ContentCalendar.
 */
class ContentCalendarPage extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $slug = 'contentkalender';

    protected static ?string $title = 'Contentkalender';

    protected static ?string $navigationLabel = 'Contentkalender';

    protected static string | \UnitEnum | null $navigationGroup = 'Content';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 3;

    protected string $view = 'dashed-core::pages.content-calendar';

    #[Url]
    public ?string $month = null;

    #[Url]
    public ?string $site = null;

    /** @var array<int, string> bronnen die uit staan */
    #[Url]
    public array $hidden = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasPanelAccess() && ContentCalendar::sources() !== [];
    }

    public function mount(): void
    {
        $this->site ??= Sites::getActive();
    }

    public function monthStart(): Carbon
    {
        return rescue(fn () => Carbon::createFromFormat('Y-m', (string) $this->month)->startOfMonth()->startOfDay(), null, report: false)
            ?? now()->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
    }

    public function thisMonth(): void
    {
        $this->month = null;
    }

    public function toggleSource(string $key): void
    {
        $this->hidden = in_array($key, $this->hidden, true)
            ? array_values(array_diff($this->hidden, [$key]))
            : array_values(array_merge($this->hidden, [$key]));
    }

    /**
     * @return array<string, string>
     */
    public function siteOptions(): array
    {
        return collect(Sites::getSites())->mapWithKeys(fn ($site) => [$site['id'] => $site['name']])->all();
    }

    /**
     * @return array{weeks: array<int, array<int, array{date: Carbon, inMonth: bool, events: array<int, CalendarEvent>}>>, sources: array<string, array{label: string, color: string, active: bool}>}
     */
    public function calendar(): array
    {
        $start = $this->monthStart();
        $end = $start->copy()->endOfMonth();
        $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $sources = ContentCalendar::sources();
        $active = array_values(array_diff(array_keys($sources), $this->hidden));
        $events = ContentCalendar::events($gridStart, $gridEnd, $this->site, $active);

        $weeks = [];
        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $dayEnd = $day->copy()->endOfDay();
            $weeks[intdiv((int) $gridStart->diffInDays($day), 7)][] = [
                'date' => $day->copy(),
                'inMonth' => $day->month === $start->month,
                'events' => $events->filter(fn (CalendarEvent $e) => $e->start->lte($dayEnd) && $e->endOr($gridEnd)->gte($day))->values()->all(),
            ];
        }

        return [
            'weeks' => $weeks,
            'sources' => collect($sources)->map(fn ($s, $key) => ['label' => $s['label'], 'color' => $s['color'], 'active' => in_array($key, $active, true)])->all(),
        ];
    }
}

<?php

namespace Dashed\DashedCore\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Illuminate\Support\Carbon;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Calendar\CalendarEvent;
use Dashed\DashedCore\Classes\Calendar\CampaignCalendar;

/**
 * Campagnekalender: een maand met kortingscodes, popups, nieuwsbrieven en
 * ingeplande pagina's per site, met bovenaan wat elkaar in de weg zit. Een
 * eigen raster in plaats van een kalenderbibliotheek: het is een overzicht,
 * slepen en bewerken gebeurt op de schermen zelf.
 */
class CampaignCalendarPage extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $slug = 'campagnekalender';

    protected static ?string $title = 'Campagnekalender';

    protected static ?string $navigationLabel = 'Campagnekalender';

    protected static string | \UnitEnum | null $navigationGroup = 'Communicatie';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    protected string $view = 'dashed-core::pages.campaign-calendar';

    #[Url]
    public ?string $month = null;

    #[Url]
    public ?string $site = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->role ?? 'customer') !== 'customer' && CampaignCalendar::sources() !== [];
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

    /**
     * @return array<string, string>
     */
    public function siteOptions(): array
    {
        return collect(Sites::getSites())->mapWithKeys(fn ($site) => [$site['id'] => $site['name']])->all();
    }

    /**
     * Weken van maandag tot zondag, met per dag de events die erop vallen.
     *
     * @return array{weeks: array<int, array<int, array{date: Carbon, inMonth: bool, events: array<int, CalendarEvent>}>>, conflicts: array<int, array<string, mixed>>, sources: array<string, array{label: string, color: string}>}
     */
    public function calendar(): array
    {
        $start = $this->monthStart();
        $end = $start->copy()->endOfMonth();
        $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

        $events = CampaignCalendar::events($gridStart, $gridEnd, $this->site);

        $weeks = [];
        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $dayEnd = $day->copy()->endOfDay();
            $weeks[intdiv($gridStart->diffInDays($day), 7)][] = [
                'date' => $day->copy(),
                'inMonth' => $day->month === $start->month,
                'events' => $events->filter(fn (CalendarEvent $e) => $e->start->lte($dayEnd) && $e->endOr($gridEnd)->gte($day))->values()->all(),
            ];
        }

        return [
            'weeks' => $weeks,
            'conflicts' => CampaignCalendar::conflicts($events, $gridEnd),
            'sources' => collect(CampaignCalendar::sources())->map(fn ($s) => ['label' => $s['label'], 'color' => $s['color']])->all(),
        ];
    }
}

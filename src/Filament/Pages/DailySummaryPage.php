<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Dashed\DashedCore\Services\Summary\DailySummaryBuilder;

/**
 * Dagoverzicht: toont per dag dezelfde samenvatting-secties als de
 * samenvatting-mail (omzet, bestellingen, verlaten winkelwagens incl.
 * verzonden mails, verzending, popups, formulieren, AI-briefing). Met
 * datumnavigatie om terug te bladeren.
 */
class DailySummaryPage extends Page
{
    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 5;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Dagoverzicht';

    protected static ?string $title = 'Dagoverzicht';

    protected static ?string $slug = 'dagoverzicht';

    protected string $view = 'dashed-core::pages.daily-summary';

    /** Geselecteerde dag (YYYY-MM-DD). */
    public string $date = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user();
    }

    public function mount(): void
    {
        $this->date = Carbon::yesterday()->toDateString();
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $next = Carbon::parse($this->date)->addDay();
        if (! $next->copy()->startOfDay()->isFuture()) {
            $this->date = $next->toDateString();
        }
    }

    /** @return array{date:string,label:string,sections:array<int,array<string,mixed>>} */
    public function getSummaryProperty(): array
    {
        return DailySummaryBuilder::buildForDate(Carbon::parse($this->date));
    }

    public function getIsTodayProperty(): bool
    {
        return Carbon::parse($this->date)->isToday();
    }
}

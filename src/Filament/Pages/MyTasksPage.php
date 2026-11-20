<?php

namespace Dashed\DashedCore\Filament\Pages;

use Livewire\Attributes\Url;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Str;
use Filament\Tables\Columns\TextColumn;
use Dashed\DashedCore\Models\Reminder;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Dashed\DashedCore\Classes\Tasks\TaskSources;
use Illuminate\Pagination\LengthAwarePaginator;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Mijn taken: bovenaan een tegel per bron met open werk (geregistreerd via
 * TaskSources) plus de eigen herinneringen; een tegel aanklikken filtert de
 * tabel eronder. De tabel toont alles: de open records van elke bron en de
 * open herinneringen van de ingelogde gebruiker, oudste eerst. Ook de plek waar
 * je herinneringen per mail aan- of uitzet; bel en app krijgen ze altijd.
 */
class MyTasksPage extends Page implements HasTable
{
    use InteractsWithTable;
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    public const REMINDERS = 'reminders';

    protected static ?string $slug = 'mijn-taken';

    protected static ?string $title = 'Mijn taken';

    protected static ?string $navigationLabel = 'Mijn taken';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = -10;

    protected string $view = 'dashed-core::pages.my-tasks';

    /** De gekozen tegel (bron-key), of null voor alles. */
    #[Url(as: 'bron')]
    public ?string $source = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->role ?? 'customer') !== 'customer';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = rescue(fn () => Reminder::query()
            ->open()
            ->where('user_id', auth()->id())
            ->where('remind_at', '<=', now()->endOfDay())
            ->count(), 0, report: false);

        $count += rescue(fn () => TaskSources::openCount(), 0, report: false);

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Tegels: eerst de eigen herinneringen, dan de bronnen met open werk.
     *
     * @return array<int, array{key: string, label: string, count: int, url: ?string, icon: mixed, group: ?string, has_items: bool}>
     */
    public function getTaskTiles(): array
    {
        return once(function () {
            $tiles = TaskSources::open();

            $reminders = rescue(fn () => Reminder::query()->open()->where('user_id', auth()->id())->count(), 0, report: false);

            if ($reminders > 0) {
                array_unshift($tiles, [
                    'key' => self::REMINDERS,
                    'label' => __('Herinneringen'),
                    'count' => $reminders,
                    'url' => null,
                    'icon' => 'heroicon-o-bell-alert',
                    'group' => __('Mijn taken'),
                    'sort' => 0,
                    'has_items' => true,
                ]);
            }

            return $tiles;
        });
    }

    /**
     * Tegel aanklikken: filter de tabel op die bron, nog eens klikken toont weer alles.
     */
    public function filterSource(?string $key): void
    {
        $this->source = ($key === null || $key === $this->source) ? null : $key;
        $this->flushCachedTableRecords();
        $this->resetPage($this->getTablePaginationPageName());
    }

    protected function getHeaderActions(): array
    {
        $byMail = (bool) auth()->user()?->reminders_by_mail;

        return [
            Action::make('toggleMail')
                ->label($byMail ? __('Mail staat aan') : __('Ook per mail'))
                ->icon($byMail ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                ->color($byMail ? 'success' : 'gray')
                ->tooltip(__('Herinneringen komen altijd in de bel en in de app; hiermee krijg je ze ook per mail.'))
                ->action(function () use ($byMail) {
                    auth()->user()->forceFill(['reminders_by_mail' => ! $byMail])->save();
                }),
        ];
    }

    /**
     * Alle open taken als rijen: herinneringen plus de records van elke bron.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function taskRows(): array
    {
        $rows = [];

        if ($this->source === null || $this->source === self::REMINDERS) {
            $reminders = rescue(fn () => Reminder::query()->open()->where('user_id', auth()->id())->with('creator')->get(), collect(), report: false);

            foreach ($reminders as $reminder) {
                $from = $reminder->created_by === $reminder->user_id ? __('jezelf') : $reminder->creator?->name;

                $rows[] = [
                    'key' => self::REMINDERS . '|' . $reminder->id,
                    'source' => self::REMINDERS,
                    'label' => __('Herinnering'),
                    'group' => $from ? __('Van :naam', ['naam' => $from]) : null,
                    'title' => (string) $reminder->label,
                    'description' => $reminder->note ? Str::limit($reminder->note, 120) : null,
                    'date' => $reminder->remind_at,
                    'url' => $reminder->url,
                    'reminder_id' => $reminder->id,
                    'overdue' => $reminder->isOverdue(),
                    'today' => $reminder->remind_at?->isToday() ?? false,
                ];
            }
        }

        if ($this->source !== self::REMINDERS) {
            array_push($rows, ...TaskSources::items($this->source));
        }

        return $rows;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, ?string $sortColumn, ?string $sortDirection, int | string $page, int | string $recordsPerPage) {
                $rows = collect($this->taskRows());

                if (filled($search)) {
                    $needle = Str::lower($search);
                    $rows = $rows->filter(fn (array $row) => Str::contains(Str::lower($row['title'] . ' ' . $row['description'] . ' ' . $row['label']), $needle));
                }

                $descending = $sortColumn === 'date' && $sortDirection === 'desc';
                $rows = $rows->sortBy(fn (array $row) => $row['date']?->getTimestamp() ?? PHP_INT_MAX, descending: $descending)->values();

                $perPage = $recordsPerPage === 'all' ? max($rows->count(), 1) : (int) $recordsPerPage;

                return new LengthAwarePaginator(
                    $rows->forPage((int) $page, $perPage)->values(),
                    $rows->count(),
                    $perPage,
                    (int) $page,
                );
            })
            ->recordUrl(fn (array $record) => $record['url'] ?? null)
            ->openRecordUrlInNewTab()
            ->searchable()
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('date')
                    ->label(__('Wanneer'))
                    ->dateTime('j M Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->color(fn (array $record) => ($record['overdue'] ?? false) ? 'danger' : (($record['today'] ?? false) ? 'warning' : null))
                    ->description(fn (array $record) => match (true) {
                        $record['overdue'] ?? false => __('Te laat'),
                        $record['today'] ?? false => __('Vandaag'),
                        default => $record['date']?->diffForHumans(),
                    }),
                TextColumn::make('label')
                    ->label(__('Soort'))
                    ->badge()
                    ->color(fn (array $record) => $record['source'] === self::REMINDERS ? 'warning' : 'gray')
                    ->description(fn (array $record) => $record['group'] ?? null),
                TextColumn::make('title')
                    ->label(__('Waarover'))
                    ->weight('medium')
                    ->description(fn (array $record) => $record['description'] ?? null)
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label(__('Afgerond'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (array $record) => isset($record['reminder_id']))
                    ->action(function (array $record) {
                        Reminder::find($record['reminder_id'])?->complete();
                        Notification::make()->title(__('Afgerond'))->success()->send();
                    }),
                Action::make('snooze')
                    ->label(__('Morgen'))
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->visible(fn (array $record) => isset($record['reminder_id']))
                    ->action(fn (array $record) => Reminder::find($record['reminder_id'])?->snooze(now()->addDay())),
                Action::make('delete')
                    ->label(__('Verwijderen'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (array $record) => isset($record['reminder_id']))
                    ->action(fn (array $record) => Reminder::find($record['reminder_id'])?->delete()),
            ])
            ->emptyStateHeading(__('Geen open taken'))
            ->emptyStateDescription(__('Plan er een met "Herinner mij" bovenaan een bestelling, klant, product of pagina.'));
    }
}

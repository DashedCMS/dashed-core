<?php

namespace Dashed\DashedCore\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Dashed\DashedCore\Models\Reminder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Mijn taken: de open herinneringen en taken van de ingelogde gebruiker, te
 * laat bovenaan. Ook de plek waar je herinneringen per mail aan- of uitzet;
 * bel en app krijgen ze altijd.
 */
class MyTasksPage extends Page implements HasTable
{
    use InteractsWithTable;
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $slug = 'mijn-taken';

    protected static ?string $title = 'Mijn taken';

    protected static ?string $navigationLabel = 'Mijn taken';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = -10;

    protected string $view = 'dashed-core::pages.my-tasks';

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

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Reminder::query()->open()->where('user_id', auth()->id())->with('creator'))
            ->defaultSort('remind_at')
            ->columns([
                TextColumn::make('remind_at')
                    ->label(__('Wanneer'))
                    ->dateTime('j M Y H:i')
                    ->sortable()
                    ->color(fn (Reminder $record) => $record->isOverdue() ? 'danger' : ($record->remind_at->isToday() ? 'warning' : null))
                    ->description(fn (Reminder $record) => $record->isOverdue() ? __('Te laat') : ($record->remind_at->isToday() ? __('Vandaag') : null)),
                TextColumn::make('label')
                    ->label(__('Waarover'))
                    ->searchable()
                    ->url(fn (Reminder $record) => $record->url)
                    ->description(fn (Reminder $record) => $record->note ? \Illuminate\Support\Str::limit($record->note, 120) : null)
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label(__('Van'))
                    ->formatStateUsing(fn ($state, Reminder $record) => $record->created_by === $record->user_id ? __('Jezelf') : $state),
            ])
            ->filters([
                SelectFilter::make('wanneer')
                    ->label(__('Wanneer'))
                    ->options([
                        'today' => __('Vandaag'),
                        'overdue' => __('Te laat'),
                        'upcoming' => __('Komend'),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'today' => $query->whereBetween('remind_at', [now()->startOfDay(), now()->endOfDay()]),
                        'overdue' => $query->where('remind_at', '<', now()->startOfDay()),
                        'upcoming' => $query->where('remind_at', '>', now()->endOfDay()),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label(__('Afgerond'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Reminder $record) {
                        $record->complete();
                        Notification::make()->title(__('Afgerond'))->success()->send();
                    }),
                Action::make('snooze')
                    ->label(__('Morgen'))
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->action(fn (Reminder $record) => $record->snooze(now()->addDay())),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('Geen open taken'))
            ->emptyStateDescription(__('Plan er een met "Herinner mij" bovenaan een bestelling, klant, product of pagina.'));
    }
}

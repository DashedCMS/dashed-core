<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Throwable;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Pagination\LengthAwarePaginator;
use Filament\Tables\Concerns\InteractsWithTable;
use Dashed\DashedCore\Classes\AdminActionMonitor;
use Dashed\DashedCore\Classes\Mail\PostmarkSuppressions;
use Dashed\DashedCore\Events\EmailSuppressionReactivated;

/**
 * De blokkadelijst van Postmark inzien en adressen heractiveren.
 *
 * Alleen voor superadmins: een hard bounce heractiveren en er daarna opnieuw
 * op bouncen kost afzenderreputatie, en dat geldt voor de hele server.
 */
class EmailSuppressionsPage extends Page implements HasTable
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Geblokkeerde e-mailadressen';

    protected string $view = 'dashed-core::settings.pages.email-suppressions';

    public const CACHE_SECONDS = 300;

    public ?string $loadError = null;

    public static function canAccess(): bool
    {
        return (auth()->user()?->isSuperadmin() ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, array $filters, int | string $page, int | string $recordsPerPage) => $this->rows($search, $filters, (int) $page, (int) $recordsPerPage))
            ->columns([
                TextColumn::make('email')
                    ->label(__('E-mailadres'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('reason')
                    ->label(__('Reden'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'HardBounce' => 'danger',
                        'SpamComplaint' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => PostmarkSuppressions::reasonLabel($state)),
                TextColumn::make('origin')
                    ->label(__('Door'))
                    ->formatStateUsing(fn (string $state) => PostmarkSuppressions::originLabel($state)),
                TextColumn::make('created_at')
                    ->label(__('Sinds'))
                    ->dateTime('d-m-Y H:i'),
            ])
            ->filters([
                SelectFilter::make('stream')
                    ->label(__('Stream'))
                    ->options(fn () => $this->streams())
                    ->default(fn () => array_key_first($this->streams()))
                    ->selectablePlaceholder(false),
                SelectFilter::make('reason')
                    ->label(__('Reden'))
                    ->options(collect(PostmarkSuppressions::REASONS)
                        ->mapWithKeys(fn (string $reason) => [$reason => PostmarkSuppressions::reasonLabel($reason)])
                        ->all()),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->label(__('Vernieuwen'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn () => $this->forgetCache()),
            ])
            ->recordActions([
                Action::make('reactivate')
                    ->label(__('Heractiveren'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalDescription(__('Postmark stuurt dan weer mail naar dit adres. Bounct het opnieuw, dan komt het vanzelf terug op de lijst en telt dat mee voor de afzenderreputatie.'))
                    ->visible(fn (array $record) => $record['reason'] !== 'SpamComplaint')
                    ->action(fn (array $record) => $this->reactivate([$record['email']])),
            ])
            ->toolbarActions([
                BulkAction::make('reactivateSelected')
                    ->label(__('Heractiveren'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalDescription(__('Postmark stuurt dan weer mail naar deze adressen. Spamklachten kan Postmark niet opheffen; die blijven staan.'))
                    ->action(fn (Collection $records) => $this->reactivate(
                        $records->reject(fn (array $record) => $record['reason'] === 'SpamComplaint')->pluck('email')->all()
                    )),
            ])
            ->emptyStateHeading(fn () => $this->loadError ? __('Kon de lijst niet ophalen') : __('Geen geblokkeerde adressen'))
            ->emptyStateDescription(fn () => $this->loadError);
    }

    public function currentStream(): ?string
    {
        return $this->tableFilters['stream']['value'] ?? array_key_first($this->streams());
    }

    /**
     * @return array<string, string>
     */
    public function streams(): array
    {
        if (! PostmarkSuppressions::configured()) {
            return [];
        }

        try {
            return Cache::remember('dashed:postmark:streams', static::CACHE_SECONDS, fn () => PostmarkSuppressions::streams());
        } catch (Throwable $e) {
            $this->loadError = __('Postmark gaf een fout: :fout', ['fout' => $e->getMessage()]);

            return [];
        }
    }

    protected function rows(?string $search, array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $rows = collect();
        $stream = $filters['stream']['value'] ?? array_key_first($this->streams());
        $reason = $filters['reason']['value'] ?? null;

        if (! PostmarkSuppressions::configured()) {
            $this->loadError = __('Er is geen Postmark-token ingesteld (POSTMARK_TOKEN).');
        } elseif ($stream) {
            try {
                $rows = collect(Cache::remember(
                    $this->cacheKey($stream, $reason),
                    static::CACHE_SECONDS,
                    fn () => PostmarkSuppressions::list($stream, $reason),
                ));
            } catch (Throwable $e) {
                $this->loadError = __('Postmark gaf een fout: :fout', ['fout' => $e->getMessage()]);
            }
        }

        if (filled($search)) {
            $needle = mb_strtolower(trim($search));
            $rows = $rows->filter(fn (array $row) => str_contains(mb_strtolower($row['email']), $needle));
        }

        $rows = $rows->sortByDesc('created_at')->keyBy('email');
        $perPage = max(1, $perPage);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage),
            $rows->count(),
            $perPage,
            $page,
        );
    }

    /**
     * @param  array<int, string>  $emails
     */
    public function reactivate(array $emails): void
    {
        $stream = $this->currentStream();

        if (! $stream || $emails === []) {
            return;
        }

        try {
            $result = PostmarkSuppressions::reactivate($stream, $emails);
        } catch (Throwable $e) {
            Log::warning('[postmark-suppressions] heractiveren mislukt', ['stream' => $stream, 'error' => $e->getMessage()]);

            Notification::make()
                ->danger()
                ->title(__('Heractiveren mislukt'))
                ->body($e->getMessage())
                ->send();

            return;
        }

        foreach ($result['deleted'] as $email) {
            EmailSuppressionReactivated::dispatch($email, $stream);

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['email' => $email, 'stream' => $stream])
                ->log('security:email-suppression-reactivated');
        }

        if ($result['deleted'] !== []) {
            AdminActionMonitor::alert(__('E-mailadressen heractiveerd bij Postmark'), [
                __('Stream') => $stream,
                __('Adressen') => implode(', ', $result['deleted']),
            ]);
        }

        $this->forgetCache();

        $notification = Notification::make()
            ->title(count($result['deleted']) === 1
                ? __('1 adres heractiveerd')
                : __(':count adressen heractiveerd', ['count' => count($result['deleted'])]));

        if ($result['failed'] !== []) {
            $notification->warning()->body(collect($result['failed'])
                ->map(fn (string $message, string $email) => "{$email}: {$message}")
                ->implode("\n"));
        } else {
            $notification->success();
        }

        $notification->send();
    }

    protected function forgetCache(): void
    {
        Cache::forget('dashed:postmark:streams');

        foreach (array_keys($this->streams()) as $stream) {
            foreach ([null, ...PostmarkSuppressions::REASONS] as $reason) {
                Cache::forget($this->cacheKey($stream, $reason));
            }
        }
    }

    protected function cacheKey(string $stream, ?string $reason): string
    {
        return 'dashed:postmark:suppressions:' . $stream . ':' . ($reason ?? 'all');
    }
}

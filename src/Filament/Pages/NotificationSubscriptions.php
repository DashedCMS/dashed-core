<?php

namespace Dashed\DashedCore\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Dashed\DashedCore\Mail\SummaryMail;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\SummarySubscription;
use Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * Per-user pagina waar elke admin zijn samenvatting-mail-voorkeuren
 * instelt. Toont 1 select per geregistreerde SummaryContributor met
 * de toegestane frequenties (uit / dagelijks / wekelijks / maandelijks).
 */
class NotificationSubscriptions extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $title = 'Mijn samenvattingen';

    protected static ?string $navigationLabel = 'Mijn samenvattingen';

    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?string $slug = 'me/summary-subscriptions';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->check();
    }

    public function mount(): void
    {
        $defaults = [];

        foreach ($this->resolveContributors() as $key => $class) {
            $defaults[$this->fieldKey($key)] = $this->resolveCurrentFrequency($key, $class);
        }

        $this->form->fill($defaults);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach ($this->resolveContributors() as $key => $class) {
            $sections[] = Section::make($class::label())
                ->description($class::description())
                ->schema([
                    Select::make($this->fieldKey($key))
                        ->label('Frequentie')
                        ->options($this->frequencyOptions($class::availableFrequencies()))
                        ->default($this->resolveCurrentFrequency($key, $class))
                        ->required()
                        ->helperText($class::description()),
                ]);
        }

        if (empty($sections)) {
            $sections[] = Section::make('Nog geen samenvattings beschikbaar')
                ->description('Er zijn nog geen modules geinstalleerd die samenvatting-mails aanleveren. Activeer een dashed-package zoals dashed-ecommerce-core, dashed-popups of dashed-marketing om hier opties te zien verschijnen.')
                ->schema([]);
        }

        return $schema->schema($sections)->statePath('data');
    }

    public function submit(): void
    {
        $userId = (int) auth()->id();
        $formData = $this->form->getState();

        foreach ($this->resolveContributors() as $key => $class) {
            $field = $this->fieldKey($key);
            $value = (string) ($formData[$field] ?? 'off');
            $allowed = array_merge(['off'], array_values($class::availableFrequencies()));

            if (! in_array($value, $allowed, true)) {
                $value = 'off';
            }

            SummarySubscription::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'contributor_key' => $key,
                ],
                [
                    'frequency' => $value,
                    // Reset next_send_at zodat de scheduler direct opnieuw plant.
                    'next_send_at' => $value === 'off' ? null : null,
                ],
            );
        }

        Notification::make()
            ->title('Voorkeuren opgeslagen')
            ->success()
            ->send();

        redirect(static::getUrl());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestNow')
                ->label('Stuur testmail nu')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action(function (): void {
                    $this->sendTestMail();
                }),
        ];
    }

    /**
     * Verstuurt een testmail synchronously voor alle ACTIEVE
     * subscriptions van de huidige user. Gebruikt per subscription
     * een eigen periode op basis van de gekozen frequency. Update
     * next_send_at NIET zodat het puur een preview blijft.
     */
    protected function sendTestMail(): void
    {
        try {
            $user = auth()->user();
            if (! $user || empty($user->email)) {
                Notification::make()->title('Geen e-mailadres bekend')->danger()->send();

                return;
            }

            $subs = SummarySubscription::query()
                ->where('user_id', $user->id)
                ->where('frequency', '!=', 'off')
                ->get();

            if ($subs->isEmpty()) {
                Notification::make()
                    ->title('Geen actieve samenvattings')
                    ->body('Schakel ten minste 1 sectie in voordat je een testmail verstuurt.')
                    ->warning()
                    ->send();

                return;
            }

            $registry = $this->resolveContributors();
            $sections = [];
            $period = $this->periodFor('weekly');

            foreach ($subs as $sub) {
                $class = $registry[(string) $sub->contributor_key] ?? null;
                if (! $class) {
                    continue;
                }

                $sectionPeriod = $this->periodFor((string) $sub->frequency) ?? $period;
                try {
                    $section = $class::contribute($sectionPeriod);
                } catch (Throwable $e) {
                    report($e);
                    $section = null;
                }

                if ($section !== null) {
                    $sections[] = $section;
                    $period = $sectionPeriod;
                }
            }

            if (empty($sections)) {
                Notification::make()
                    ->title('Geen data voor de testmail')
                    ->body('De geselecteerde samenvattings hadden geen data voor de gekozen periode.')
                    ->warning()
                    ->send();

                return;
            }

            Mail::to($user->email)->send(new SummaryMail($user, $sections, $period));

            Notification::make()
                ->title('Testmail verstuurd')
                ->body('Bekijk je inbox op ' . $user->email . '.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Testmail mislukt')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<string, class-string<SummaryContributorInterface>>
     */
    protected function resolveContributors(): array
    {
        $registered = cms()->builder('summaryContributors', null) ?? [];
        if (! is_array($registered)) {
            return [];
        }

        $map = [];
        foreach ($registered as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }
            if (! is_subclass_of($class, SummaryContributorInterface::class)) {
                continue;
            }

            try {
                /** @var class-string<SummaryContributorInterface> $class */
                $map[$class::key()] = $class;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $map;
    }

    protected function resolveCurrentFrequency(string $key, string $class): string
    {
        $existing = SummarySubscription::query()
            ->where('user_id', (int) auth()->id())
            ->where('contributor_key', $key)
            ->first();

        if ($existing) {
            return (string) $existing->frequency;
        }

        $default = Customsetting::get("summary_default_{$key}");
        if (is_string($default) && $default !== '') {
            return $default;
        }

        /** @var class-string<SummaryContributorInterface> $class */
        return $class::defaultFrequency();
    }

    /**
     * @param  array<int, string>  $available
     * @return array<string, string>
     */
    protected function frequencyOptions(array $available): array
    {
        $labels = [
            'off' => 'Uit',
            'daily' => 'Dagelijks',
            'weekly' => 'Wekelijks',
            'monthly' => 'Maandelijks',
        ];

        $options = ['off' => $labels['off']];
        foreach ($available as $freq) {
            if (isset($labels[$freq])) {
                $options[$freq] = $labels[$freq];
            }
        }

        return $options;
    }

    protected function fieldKey(string $contributorKey): string
    {
        return 'frequency_' . $contributorKey;
    }

    protected function periodFor(string $frequency): ?SummaryPeriod
    {
        return match ($frequency) {
            'daily' => new SummaryPeriod(
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
                'daily',
                'Gisteren',
            ),
            'weekly' => new SummaryPeriod(
                Carbon::now()->subDays(7)->startOfDay(),
                Carbon::now()->subDay()->endOfDay(),
                'weekly',
                'Afgelopen 7 dagen',
            ),
            'monthly' => new SummaryPeriod(
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
                'monthly',
                Carbon::now()->subMonthNoOverflow()->translatedFormat('F Y'),
            ),
            default => null,
        };
    }
}

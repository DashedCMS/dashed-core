<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Throwable;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Notifications\DTOs\TelegramSummary;
use Dashed\DashedCore\Services\Summary\SummaryPeriodFactory;
use Dashed\DashedCore\Notifications\Channels\TelegramChannel;
use Dashed\DashedCore\Services\Summary\Renderers\TelegramSectionRenderer;
use Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface;

class NotificationSettingsPage extends Page implements HasSchemas
{
    use HasSettingsPermission;
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Notificatie instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
        $values = [
            'telegram_enabled' => (bool) Customsetting::get('telegram_enabled'),
            'telegram_bot_token' => Customsetting::get('telegram_bot_token'),
            'telegram_chat_id' => Customsetting::get('telegram_chat_id'),
        ];

        foreach ($this->resolveSummaryContributors() as $key => $class) {
            $values[$this->summaryFieldKey($key)] = (string) (
                Customsetting::get("summary_default_{$key}") ?: $class::defaultFrequency()
            );
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [
            Section::make('Telegram')
                ->description('Stuur admin-notificaties (nieuwe orders, formulier inzendingen, lage voorraad, etc.) als beknopte berichten naar een Telegram chat. Mail blijft altijd verstuurd worden, Telegram is een aanvullend kanaal.')
                ->schema([
                    Toggle::make('telegram_enabled')
                        ->label('Telegram kanaal actief')
                        ->helperText('Zet uit om alle Telegram berichten te pauzeren zonder de instellingen te wissen.'),
                    TextInput::make('telegram_bot_token')
                        ->label('Bot token')
                        ->password()
                        ->revealable()
                        ->helperText('Maak een bot via @BotFather op Telegram en kopieer het token. Format: 123456:ABC-DEF...')
                        ->nullable(),
                    TextInput::make('telegram_chat_id')
                        ->label('Chat ID')
                        ->helperText('Voeg de bot toe aan een (groeps)chat. Gebruik @userinfobot of stuur /start in de groep en haal het chat_id uit de getUpdates response. Voor groepen begint het meestal met -100.')
                        ->nullable(),
                ]),
        ];

        $summaryFields = [];
        foreach ($this->resolveSummaryContributors() as $key => $class) {
            $available = $class::availableFrequencies();
            $summaryFields[] = Select::make($this->summaryFieldKey($key))
                ->label($class::label())
                ->options($this->summaryFrequencyOptions($available))
                ->default($class::defaultFrequency())
                ->required()
                ->helperText($class::description() . ' Wijzigingen gelden alleen voor nieuwe gebruikers, bestaande voorkeuren blijven staan.');
        }

        if (! empty($summaryFields)) {
            $sections[] = Section::make('Samenvattings-defaults')
                ->description('Standaardfrequenties voor nieuwe admin-gebruikers. Bestaande gebruikers zien hun eigen voorkeuren via "Mijn samenvattings".')
                ->schema($summaryFields);
        }

        return $schema->schema($sections)->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();
        $contributors = $this->resolveSummaryContributors();

        foreach (Sites::getSites() as $site) {
            Customsetting::set('telegram_enabled', (bool) ($formData['telegram_enabled'] ?? false), $site['id']);
            Customsetting::set('telegram_bot_token', $formData['telegram_bot_token'] ?? null, $site['id']);
            Customsetting::set('telegram_chat_id', $formData['telegram_chat_id'] ?? null, $site['id']);

            foreach ($contributors as $key => $class) {
                $field = $this->summaryFieldKey($key);
                $value = (string) ($formData[$field] ?? $class::defaultFrequency());
                $allowed = array_merge(['off'], array_values($class::availableFrequencies()));
                if (! in_array($value, $allowed, true)) {
                    $value = 'off';
                }

                Customsetting::set("summary_default_{$key}", $value, $site['id']);
            }
        }

        Notification::make()
            ->title('Notificatie instellingen opgeslagen')
            ->success()
            ->send();

        redirect(self::getUrl());
    }

    /**
     * @return array<string, class-string<SummaryContributorInterface>>
     */
    protected function resolveSummaryContributors(): array
    {
        $registered = function_exists('cms') ? (cms()->builder('summaryContributors', null) ?? []) : [];
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

    protected function summaryFieldKey(string $contributorKey): string
    {
        return 'summary_default_' . $contributorKey;
    }

    /**
     * @param  array<int, string>  $available
     * @return array<string, string>
     */
    protected function summaryFrequencyOptions(array $available): array
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

    protected function getHeaderActions(): array
    {
        $telegramConfigured = fn () => app(TelegramChannel::class)->isConfigured();

        return [
            Action::make('sendTestMessage')
                ->label('Stuur testmelding')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible($telegramConfigured)
                ->action(function (): void {
                    try {
                        app(TelegramChannel::class)->sendTestMessage();

                        Notification::make()
                            ->title('Testmelding verstuurd')
                            ->body('Check je Telegram chat.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Testmelding mislukt')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            ActionGroup::make([
                Action::make('testSummaryDaily')
                    ->label('Dagelijks')
                    ->action(fn () => $this->dispatchSummaryTest('daily')),
                Action::make('testSummaryWeekly')
                    ->label('Wekelijks')
                    ->action(fn () => $this->dispatchSummaryTest('weekly')),
                Action::make('testSummaryMonthly')
                    ->label('Maandelijks')
                    ->action(fn () => $this->dispatchSummaryTest('monthly')),
            ])
                ->label('Stuur test-samenvatting')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->button()
                ->visible(fn () => $telegramConfigured() && ! empty($this->resolveSummaryContributors())),
        ];
    }

    /**
     * Voert een ad-hoc test uit voor de opgegeven frequentie: roept
     * elke contributor aan die deze frequentie ondersteunt, en stuurt
     * de gegenereerde secties als losse berichten naar Telegram. Een
     * contributor zonder data wordt overgeslagen.
     */
    protected function dispatchSummaryTest(string $frequency): void
    {
        $period = SummaryPeriodFactory::make($frequency);
        if ($period === null) {
            Notification::make()
                ->title('Onbekende periode')
                ->body('Frequentie "' . $frequency . '" wordt niet ondersteund.')
                ->danger()
                ->send();

            return;
        }

        $contributors = $this->resolveSummaryContributors();
        if ($contributors === []) {
            Notification::make()
                ->title('Geen contributors')
                ->body('Er zijn geen samenvattings-modules geregistreerd.')
                ->warning()
                ->send();

            return;
        }

        $channel = app(TelegramChannel::class);
        $sectionsMarkdown = [];
        $withData = 0;
        $withoutData = 0;
        $failed = [];

        $frequencyLabel = match ($frequency) {
            'daily' => 'Dagelijkse samenvatting',
            'weekly' => 'Wekelijkse samenvatting',
            'monthly' => 'Maandelijkse samenvatting',
            default => 'Samenvatting',
        };

        $header = new TelegramSummary(
            title: $frequencyLabel . ' — ' . $period->label,
            emoji: '📊',
        );
        $sectionsMarkdown[] = $header->toMarkdown();

        foreach ($contributors as $key => $class) {
            if (! $this->contributorMatchesFrequency($class, $frequency)) {
                continue;
            }

            try {
                $section = $class::contribute($period);
            } catch (Throwable $e) {
                report($e);
                $failed[] = $class::label();

                continue;
            }

            if ($section === null) {
                $placeholder = new TelegramSummary(
                    title: $class::label(),
                    fields: ['Status' => 'Geen data in deze periode'],
                    emoji: 'ℹ️',
                );
                $sectionsMarkdown[] = $placeholder->toMarkdown();
                $withoutData++;

                continue;
            }

            $sectionsMarkdown[] = TelegramSectionRenderer::render($section, $period)->toMarkdown();
            $withData++;
        }

        try {
            $channel->sendRaw(implode("\n\n", $sectionsMarkdown));
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Telegram-verzending mislukt')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($failed !== []) {
            Notification::make()
                ->title('Test deels mislukt')
                ->body(sprintf(
                    'Bericht verstuurd met %d module(s) (%d zonder data). Mislukt: %s',
                    $withData,
                    $withoutData,
                    implode(', ', $failed),
                ))
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Test-samenvatting verstuurd')
            ->body(sprintf(
                '%d module(s) gebundeld in één Telegram-bericht (%d zonder data) voor: %s',
                $withData,
                $withoutData,
                $period->label,
            ))
            ->success()
            ->send();
    }

    /**
     * Bepaalt of een contributor meegaat in een test van de opgegeven
     * frequentie. Hiërarchie: een dagelijkse contributor hoort ook in
     * de weekly- en monthly-test, een weekly-contributor ook in monthly.
     * Een puur monthly-rapport blijft monthly-only.
     *
     * @param  class-string<SummaryContributorInterface>  $class
     */
    protected function contributorMatchesFrequency(string $class, string $frequency): bool
    {
        $rank = ['daily' => 1, 'weekly' => 2, 'monthly' => 3];
        $testRank = $rank[$frequency] ?? null;
        if ($testRank === null) {
            return false;
        }

        $minSupportedRank = null;
        foreach ($class::availableFrequencies() as $available) {
            $r = $rank[$available] ?? null;
            if ($r === null) {
                continue;
            }
            if ($minSupportedRank === null || $r < $minSupportedRank) {
                $minSupportedRank = $r;
            }
        }

        if ($minSupportedRank === null) {
            return false;
        }

        return $testRank >= $minSupportedRank;
    }
}

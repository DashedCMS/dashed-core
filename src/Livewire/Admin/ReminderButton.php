<?php

namespace Dashed\DashedCore\Livewire\Admin;

use Livewire\Component;
use Filament\Actions\Action;
use Livewire\Attributes\Locked;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Models\Reminder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;

/**
 * Knop "Herinner mij" in de kop van elke bewerk- en weergavepagina van een
 * record (render hook PAGE_HEADER_ACTIONS_BEFORE). Een herinnering voor
 * jezelf of een taak voor een collega, met een datum en een notitie; de
 * link terug is de pagina waar de knop op stond.
 */
class ReminderButton extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public const PRESETS = ['1d' => '+1 day', '3d' => '+3 days', '1w' => '+1 week'];

    #[Locked]
    public string $modelClass = '';

    #[Locked]
    public string $recordKey = '';

    #[Locked]
    public string $label = '';

    #[Locked]
    public string $url = '';

    public function remindAction(): Action
    {
        $open = $this->openCount();

        return Action::make('remind')
            ->label($open > 0 ? __('Herinneringen (:aantal)', ['aantal' => $open]) : __('Herinner mij'))
            ->icon('heroicon-o-bell-alert')
            ->color('gray')
            ->modalHeading(__('Herinnering of taak'))
            ->modalDescription($this->label)
            ->modalSubmitActionLabel(__('Inplannen'))
            ->schema([
                Select::make('when')
                    ->label(__('Wanneer'))
                    ->options([
                        '1d' => __('Over 1 dag'),
                        '3d' => __('Over 3 dagen'),
                        '1w' => __('Over 1 week'),
                        'custom' => __('Kies een moment'),
                    ])
                    ->default('1d')
                    ->required()
                    ->live(),
                DateTimePicker::make('remind_at')
                    ->label(__('Moment'))
                    ->seconds(false)
                    ->minDate(now())
                    ->required(fn (Get $get) => $get('when') === 'custom')
                    ->visible(fn (Get $get) => $get('when') === 'custom'),
                Select::make('user_id')
                    ->label(__('Voor'))
                    ->options(fn () => self::colleagues())
                    ->default(auth()->id())
                    ->searchable()
                    ->required(),
                Textarea::make('note')
                    ->label(__('Notitie'))
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (array $data) {
                $reminder = $this->plan($data);

                Notification::make()
                    ->title($reminder->user_id === auth()->id()
                        ? __('Herinnering ingepland voor :moment', ['moment' => $reminder->remind_at->translatedFormat('j F H:i')])
                        : __('Taak voor :naam ingepland', ['naam' => $reminder->user?->name]))
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function plan(array $data): Reminder
    {
        $at = ($data['when'] ?? '1d') === 'custom'
            ? \Illuminate\Support\Carbon::parse($data['remind_at'])
            : now()->modify(self::PRESETS[$data['when'] ?? '1d'] ?? self::PRESETS['1d']);

        // Alleen een collega uit de lijst: de waarde komt uit de staat van
        // het formulier, en een klantaccount hoort geen taken te krijgen.
        $userId = (int) ($data['user_id'] ?? auth()->id());
        if (! array_key_exists($userId, self::colleagues())) {
            $userId = (int) auth()->id();
        }

        $record = class_exists($this->modelClass) ? $this->modelClass::query()->find($this->recordKey) : null;

        return Reminder::query()->create([
            'remindable_type' => $record?->getMorphClass(),
            'remindable_id' => $record ? (string) $record->getKey() : null,
            'user_id' => $userId,
            'created_by' => auth()->id(),
            'label' => $this->label ?: __('Herinnering'),
            'url' => $this->url ?: null,
            'note' => $data['note'] ?? null,
            'remind_at' => $at,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function colleagues(): array
    {
        $model = config('auth.providers.users.model') ?: \Dashed\DashedCore\Models\User::class;

        return $model::query()
            ->whereNotNull('role')
            ->where('role', '!=', 'customer')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($user) => [$user->getKey() => $user->name ?: $user->email])
            ->all();
    }

    protected function openCount(): int
    {
        if (! class_exists($this->modelClass) || $this->recordKey === '') {
            return 0;
        }

        return Reminder::query()
            ->open()
            ->where('remindable_type', (new $this->modelClass())->getMorphClass())
            ->where('remindable_id', $this->recordKey)
            ->where(fn ($query) => $query->where('user_id', auth()->id())->orWhere('created_by', auth()->id()))
            ->count();
    }

    public function render()
    {
        return view('dashed-core::livewire.admin.reminder-button');
    }
}

<?php

namespace Dashed\DashedCore\Classes\Usage;

use Filament\Actions\DeleteAction;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\Redirect;
use Filament\Forms\Components\TextInput;

/**
 * Elke Filament-verwijderknop (ook in klantprojecten) laat bij een pagina,
 * product, categorie of formulier dat nog gebruikt wordt de lijst met plekken
 * zien, en biedt bij iets met een URL de keuze om die door te sturen. Zo
 * blijft er geen kapotte link achter zonder dat iemand het wist.
 */
class UsageDeleteGuard
{
    public const FIELD = 'usage_redirect_to';

    /** @var array<string, \Illuminate\Support\Collection> */
    protected static array $memo = [];

    /** @var array<int, string> */
    protected static array $paths = [];

    /**
     * Bij elke boot: Filament bewaart configureUsing in de ComponentManager in
     * de container, niet statisch, dus een "al gedaan"-vlag zou na een
     * herstart van de applicatie (tussen twee tests) de configuratie kwijt zijn.
     */
    public static function configure(): void
    {
        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action
                ->modalDescription(function (?Model $record) {
                    $usages = self::usages($record);

                    if ($usages->isEmpty()) {
                        return trans('filament-actions::modal.confirmation');
                    }

                    return new HtmlString(
                        '<p>'.e(trans_choice('Dit wordt nog op :aantal plek gebruikt:|Dit wordt nog op :aantal plekken gebruikt:', $usages->count(), ['aantal' => $usages->count()])).'</p>'.
                        '<ul style="margin:.5rem 0;padding-left:1.25rem;list-style:disc;text-align:left">'.
                        $usages->map(fn ($usage) => '<li>'.e($usage['source'].': '.$usage['title']).'</li>')->implode('').
                        '</ul><p>'.e(__('Na verwijderen wijzen die links nergens meer naar.')).'</p>'
                    );
                })
                ->schema(function (?Model $record): array {
                    if (self::usages($record)->isEmpty() || ! method_exists($record, 'getUrl')) {
                        return [];
                    }

                    return [
                        TextInput::make(self::FIELD)
                            ->label(__('Oude URL doorsturen naar'))
                            ->helperText(__('Bijvoorbeeld /nieuwe-pagina. Leeg laten maakt geen doorverwijzing.'))
                            ->maxLength(255),
                    ];
                })
                ->before(function (?Model $record): void {
                    self::$paths = $record && method_exists($record, 'getUrl') ? self::paths($record) : [];
                })
                ->after(function (array $data): void {
                    $to = trim((string) ($data[self::FIELD] ?? ''));

                    if ($to === '') {
                        return;
                    }

                    foreach (self::$paths as $from) {
                        if ($from !== $to && $from !== '/') {
                            Redirect::query()->firstOrCreate(['from' => $from], ['to' => $to, 'sort' => 301]);
                        }
                    }
                });
        });
    }

    protected static function usages(?Model $record): \Illuminate\Support\Collection
    {
        if (! $record || ! $record->exists || ! UsageFinder::supports($record)) {
            return collect();
        }

        return self::$memo[$record::class.':'.$record->getKey()] ??= UsageFinder::find($record);
    }

    /**
     * @return array<int, string>
     */
    protected static function paths(Model $record): array
    {
        return collect(rescue(fn () => \Dashed\DashedCore\Classes\Locales::getLocales(), [], false))
            ->map(fn ($locale) => rescue(fn () => $record->getUrl($locale['id'] ?? null), null, false))
            ->push(rescue(fn () => $record->getUrl(), null, false))
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->map(fn (string $url) => '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/'))
            ->unique()
            ->values()
            ->all();
    }

    public static function flush(): void
    {
        self::$memo = [];
        self::$paths = [];
    }
}

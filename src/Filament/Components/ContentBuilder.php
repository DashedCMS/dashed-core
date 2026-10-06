<?php

namespace Dashed\DashedCore\Filament\Components;

use ReflectionMethod;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;

use function Filament\Support\generate_icon_html;

use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;

/**
 * A Builder that keeps its items keyed by UUID, and tolerates state that isn't.
 *
 * This class exists because of a bug that made a record permanently
 * un-editable the moment someone dragged a block to a new position:
 *
 *   Builder::{closure:...getDefaultChildSchemas():949}():
 *   Argument #1 ($itemData) must be of type array, int given
 *
 * The chain was:
 *
 *  1. `CMSManager::getFilamentBuilderBlock()` normalised the state through
 *     `formatStateUsing()`. Filament implements that as `afterStateHydrated()`,
 *     which *assigns* to a single closure property — so it silently replaced
 *     `Builder::setUp()`'s own `hydrateItems()` hook.
 *  2. `hydrateItems()` is what re-keys items with UUIDs. Without it the items
 *     kept the plain 0/1/2 keys that `removeUUIDKeys()` writes to the database
 *     on every save.
 *  3. `Builder::getReorderAction()` applies the new order with
 *     `[...array_flip($arguments['items']), ...$component->getRawState()]`.
 *     Array spread only preserves *string* keys; with numeric keys both arrays
 *     are appended and renumbered, so the integers from `array_flip()` survive
 *     as state entries.
 *  4. `getDefaultChildSchemas()` types its filter closure as
 *     `fn (array $itemData)`, so the next render throws — for good.
 *
 * Dropping `formatStateUsing()` fixes the cause. This subclass covers the
 * consequences: records already corrupted this way, and the case the old
 * normalisation was written for (a locale holding a scalar or some other
 * non-block shape). `getRawState()` is the right place for it because
 * `formatStateUsing()` never actually ran in time — `hydrateState()` builds the
 * child item schemas *before* it fires hydration hooks — whereas every read of
 * the state goes through here. Since hydration and the add/clone/delete/reorder
 * actions all read-modify-write the raw state, the record also heals itself.
 */
class ContentBuilder extends Builder
{
    protected function setUp(): void
    {
        parent::setUp();

        $openLastItem = fn (Action $action): Action => $action->after(
            fn (ContentBuilder $component) => $component->openItem((string) array_key_last($component->getRawState() ?? [])),
        );

        // Een kloon van een ongemoeid blok erft de markering en dus ook de
        // databasevorm van zijn data; openItem() ontsluit en vult hem.
        $this->addAction($openLastItem);
        $this->cloneAction($openLastItem);

        // De bewerkknop (alleen bij blokvoorbeelden) leest het schema van het
        // item; een ongemoeid item heeft er geen, dus eerst ontsluiten. Gelijk
        // aan Filaments fillForm() en schema(), met alleen die stap ervoor.
        $this->editAction(fn (Action $action): Action => $action
            ->fillForm(function (array $arguments, ContentBuilder $component) {
                $component->unlockItem((string) $arguments['item']);

                return $component->getState()[$arguments['item']]['data'];
            })
            ->schema(function (array $arguments, ContentBuilder $component) {
                $component->unlockItem((string) $arguments['item']);

                return $component->getChildSchema($arguments['item'])
                    ->getClone()
                    ->getComponents(withHidden: true);
            }));

        $this->registerActions([
            fn (ContentBuilder $component): Action => $component->getInsertBlockAction(),
        ]);
    }

    public function getInsertBlockAction(): Action
    {
        return Action::make('insertBlock')
            ->label(__('Blok invoegen'))
            ->icon(Heroicon::Plus)
            ->iconButton()
            ->color('gray')
            ->size(Size::Small)
            ->modalHeading(__('Blok invoegen'))
            ->modalSubmitActionLabel(__('Invoegen'))
            ->modalWidth(Width::FourExtraLarge)
            ->schema(fn (ContentBuilder $component): array => [
                TextInput::make('search')
                    ->label(__('Zoeken'))
                    ->live(debounce: 300)
                    ->autofocus(),
                ToggleButtons::make('block')
                    ->label(__('Blok'))
                    ->required()
                    ->columns(3)
                    ->options(fn (Get $get): array => $component->insertableBlockOptions($get('search'), $get('block')))
                    ->icons($component->insertableBlockIcons()),
            ])
            ->action(function (array $arguments, array $data, ContentBuilder $component): void {
                $component->insertBlockAfter((string) $arguments['afterItem'], $data['block']);
            })
            ->visible(fn (ContentBuilder $component): bool => $component->isAddable());
    }

    /**
     * @return array<string, string>
     */
    public function insertableBlockOptions(?string $search, ?string $keep = null): array
    {
        $search = mb_strtolower(trim((string) $search));

        return collect($this->getBlockPickerBlocks())
            ->mapWithKeys(fn (Block $block): array => [$block->getName() => (string) $block->getLabel()])
            // Filtert een later gekozen zoekterm de al gekozen blok weg, dan
            // valt ToggleButtons' in-regel bij het indienen: het gekozen
            // blok blijft daarom altijd tussen de opties staan, ook als het
            // niet meer bij de zoekterm past.
            ->filter(fn (string $label, string $name): bool => $search === '' || str_contains(mb_strtolower($label), $search) || $name === $keep)
            ->all();
    }

    /**
     * @return array<string, string | \BackedEnum>
     */
    public function insertableBlockIcons(): array
    {
        return collect($this->getBlockPickerBlocks())
            ->mapWithKeys(fn (Block $block): array => [$block->getName() => $block->getIcon()])
            ->filter()
            ->all();
    }

    public function insertBlockAfter(string $afterItem, string $blockName): string
    {
        $newKey = $this->generateUuid() ?? (string) Str::uuid();
        $newItem = ['type' => $blockName, 'data' => []];
        $items = [];

        foreach ($this->getRawState() ?? [] as $key => $item) {
            $items[$key] = $item;

            if ((string) $key === $afterItem) {
                $items[$newKey] = $newItem;
            }
        }

        // Bestaat het item niet meer (net verwijderd in een ander tabblad), dan onderaan.
        $items[$newKey] ??= $newItem;

        $this->rawState($items);
        $this->getChildSchema($newKey)->fill();
        $this->openItem($newKey);
        $this->callAfterStateUpdated();
        $this->partiallyRender();

        return $newKey;
    }

    protected function openItemsSessionKey(): string
    {
        return 'dashed.content-builder.open.' . $this->getLivewire()->getId() . '.' . md5($this->getStatePath());
    }

    /**
     * @return array<string>
     */
    public function getOpenItems(): array
    {
        return session()->get($this->openItemsSessionKey(), []);
    }

    public function openItem(string $item): void
    {
        $this->unlockItem($item);

        $open = $this->getOpenItems();

        if (! in_array($item, $open, true)) {
            $open[] = $item;
        }

        session()->put($this->openItemsSessionKey(), array_values($open));
    }

    public function closeItem(string $item): void
    {
        session()->put(
            $this->openItemsSessionKey(),
            array_values(array_diff($this->getOpenItems(), [$item])),
        );
    }

    /**
     * Open is een item dat de gebruiker openklapte, of een item met een
     * validatiefout; dat laatste wordt meteen onthouden, zodat het blok niet
     * dichtklapt zodra de fout is opgelost.
     */
    public function isItemOpen(string $item): bool
    {
        if (in_array($item, $this->getOpenItems(), true)) {
            return true;
        }

        $prefix = "{$this->getStatePath()}.{$item}.";

        foreach ($this->getLivewire()->getErrorBag()->keys() as $errorKey) {
            if (str_starts_with($errorKey, $prefix)) {
                $this->openItem($item);

                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string>  $existingKeys
     */
    protected function pruneOpenItems(array $existingKeys): void
    {
        $open = $this->getOpenItems();
        $kept = array_values(array_intersect($open, $existingKeys));

        if ($kept !== $open) {
            session()->put($this->openItemsSessionKey(), $kept);
        }
    }

    /**
     * Sleutel in de ruwe state van een item (naast `type` en `data`) die zegt
     * dat het item ongemoeid is. De markering staat bewust in de state en niet
     * in de sessie: zo reist hij in dezelfde Livewire-snapshot als de data.
     * Een sessie wordt aan het eind van elk verzoek in zijn geheel
     * weggeschreven, dus een gelijktijdig verzoek (een poll) kan een oude
     * sessie terugzetten. Een item waarvan de data al gehydrateerd is, zou dan
     * weer als ongemoeid gelden en in gehydrateerde vorm opgeslagen worden.
     * mutateDehydratedState() haalt de markering er bij opslaan af.
     */
    public const UNTOUCHED_MARKER = '_dashed_untouched';

    protected bool $isHydratingAsUntouched = false;

    /**
     * Alleen een builder waarvan elk blok een kop heeft om het mee open te
     * klappen, en die zijn eigen render gebruikt, kan blokken ongemoeid
     * laten. Zonder kop (niet collapsible of geen blokkoppen) rendert elk blok
     * altijd open, en de render van Filament zelf (terugval bij een andere
     * Filament-versie) toont alleen blokken met een schema.
     */
    protected function tracksUntouchedItems(): bool
    {
        return $this->isCollapsible() && $this->hasBlockHeaders() && static::usesOwnRender() && static::knowsHydrateOrder();
    }

    /** @var array<class-string, bool> */
    protected static array $knowsHydrateOrder = [];

    protected static function expectedFilamentHydrateHash(): string
    {
        return self::FILAMENT_HYDRATE_HASH;
    }

    /**
     * Klopt de hash van Filaments hydrateState() niet (een klantproject met
     * een andere Filament-versie), dan is niet zeker dat de child-schema's
     * nog vóór hydrateItems() gehydrateerd worden. De builder bouwt dan weer
     * elk blok, zoals voorheen. Zelfde drempel voor de waarschuwing als bij
     * usesOwnRender().
     */
    public static function knowsHydrateOrder(): bool
    {
        return static::$knowsHydrateOrder[static::class] ??= (function (): bool {
            $matches = sha1(static::filamentHydrateSource()) === static::expectedFilamentHydrateHash();

            if (! $matches) {
                $version = \Composer\InstalledVersions::getVersion('filament/schemas') ?? 'unknown';

                if (Cache::add('dashed-content-builder-hydrate-mismatch:' . $version, true, now()->addDay())) {
                    Log::warning('ContentBuilder: Filament HasState::hydrateState() wijkt af, ongemoeide blokken staan uit.');
                }
            }

            return $matches;
        })();
    }

    /**
     * @return array<string>
     */
    public function getUntouchedItems(): array
    {
        if (! $this->tracksUntouchedItems()) {
            return [];
        }

        $keys = [];

        foreach ($this->getRawState() ?? [] as $key => $item) {
            if (is_array($item) && ($item[self::UNTOUCHED_MARKER] ?? false) === true) {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }

    public function isItemUntouched(string $item): bool
    {
        return in_array($item, $this->getUntouchedItems(), true);
    }

    /**
     * Na een volledige hydratie van de builder is elk item ongemoeid.
     */
    protected function markAllItemsUntouched(): void
    {
        $items = $this->getRawState() ?? [];

        foreach ($items as $key => $item) {
            $items[$key][self::UNTOUCHED_MARKER] = true;
        }

        $this->rawState($items);
        $this->clearCachedChildSchemas();
    }

    /**
     * Maakt een ongemoeid blok gewoon onderdeel van het formulier: Filament
     * bouwt vanaf nu zijn schema en vult het met zijn ruwe data, net als bij
     * een nieuw blok.
     */
    public function unlockItem(string $item): void
    {
        if (! $this->isItemUntouched($item)) {
            return;
        }

        $livewire = $this->getLivewire();
        $hadNoUnsavedChanges = static::savedDataHashMatches($livewire);

        $items = $this->getRawState();
        unset($items[$item][self::UNTOUCHED_MARKER]);
        $this->rawState($items);
        $this->clearCachedChildSchemas();

        $this->getChildSchema($item)?->fill($items[$item]['data'] ?? []);

        // Openen verandert $wire.data (markering weg, data gehydrateerd). Zonder
        // dit meldt de waarschuwing voor niet-opgeslagen wijzigingen van
        // Filament dan een wijziging die er niet is. Stond er al een echte
        // wijziging, dan blijft de oude hash staan en blijft die gemeld.
        if ($hadNoUnsavedChanges) {
            $livewire->savedDataHash = static::dataHash($livewire->data);
        }
    }

    /**
     * Zelfde formule als Filaments HasUnsavedDataChangesAlert::rememberData()
     * en unsaved-changes-alert.js.
     */
    public static function dataHash(mixed $data): string
    {
        return md5((string) str(json_encode($data, JSON_UNESCAPED_UNICODE))->replace('\\', ''));
    }

    protected static function savedDataHashMatches(object $livewire): bool
    {
        if (! property_exists($livewire, 'savedDataHash') || ! property_exists($livewire, 'data')) {
            return false;
        }

        $hash = (new \ReflectionProperty($livewire, 'savedDataHash'))->isInitialized($livewire) ? $livewire->savedDataHash : null;

        return is_string($hash) && $hash === static::dataHash($livewire->data);
    }

    /**
     * Een blok dat in deze sessie niet geopend is, heeft geen schema: Filament
     * valideert het dan niet, leest het niet uit en zoekt er niet in, en zijn
     * data gaat bij opslaan terug zoals hij uit de database kwam.
     *
     * @return array<Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        if ($this->isHydratingAsUntouched) {
            return [];
        }

        $untouched = $this->getUntouchedItems();

        return array_filter(
            parent::getDefaultChildSchemas(),
            fn ($schema, $key): bool => ! in_array((string) $key, $untouched, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Filament hydrateert eerst de child-schema's en roept daarna pas
     * afterStateHydrated (hydrateItems) aan. Zonder deze override zouden
     * alle blokken bij het vullen gehydrateerd worden en daarna als
     * ongemoeid, dus in gehydrateerde vorm, worden weggeschreven.
     *
     * @param  array<string, mixed> | null  $hydratedDefaultState
     * @param  array<string, true>  $appliedStateCastPaths
     */
    public function hydrateState(?array &$hydratedDefaultState, bool $shouldCallHydrationHooks = true, bool $shouldApplyStateCasts = true, array &$appliedStateCastPaths = []): void
    {
        if (! $this->tracksUntouchedItems()) {
            parent::hydrateState($hydratedDefaultState, $shouldCallHydrationHooks, $shouldApplyStateCasts, $appliedStateCastPaths);

            return;
        }

        $this->isHydratingAsUntouched = true;
        $this->clearCachedChildSchemas();

        try {
            parent::hydrateState($hydratedDefaultState, $shouldCallHydrationHooks, $shouldApplyStateCasts, $appliedStateCastPaths);
        } finally {
            $this->isHydratingAsUntouched = false;
        }

        $this->markAllItemsUntouched();
    }

    /**
     * Raakt een gedeeltelijke hydratie de builder zelf (of een ouderpad), dan
     * is dat een volledige hydratie van de builder en worden alle blokken weer
     * ongemoeid, net als in hydrateState().
     *
     * @param  array<string>  $statePaths
     */
    public function hydrateStatePartially(array $statePaths, bool $shouldCallHydrationHooks = true): void
    {
        $path = $this->getStatePath();
        $matches = false;

        while (filled($path)) {
            if (in_array($path, $statePaths, true)) {
                $matches = true;

                break;
            }

            $path = str_contains($path, '.') ? (string) str($path)->beforeLast('.') : '';
        }

        if ((! $matches) || (! $this->tracksUntouchedItems())) {
            parent::hydrateStatePartially($statePaths, $shouldCallHydrationHooks);

            return;
        }

        $this->isHydratingAsUntouched = true;
        $this->clearCachedChildSchemas();

        try {
            parent::hydrateStatePartially($statePaths, $shouldCallHydrationHooks);
        } finally {
            $this->isHydratingAsUntouched = false;
        }

        $this->markAllItemsUntouched();
    }

    /**
     * Altijd waar, ook als een project mutateDehydratedStateUsing() vervangt:
     * de markering mag nooit in de opgeslagen data belanden.
     */
    public function mutatesDehydratedState(): bool
    {
        return true;
    }

    public function mutateDehydratedState(mixed $state): mixed
    {
        return parent::mutateDehydratedState(static::withoutUntouchedMarkers($state));
    }

    /**
     * Haalt de markering voor ongemoeid van elk item. Voor elke plek die
     * builderdata opslaat zonder getState(), zoals de data van de andere
     * talen in HasEditableCMSActions.
     */
    public static function withoutUntouchedMarkers(mixed $items): mixed
    {
        if (! is_array($items)) {
            return $items;
        }

        foreach ($items as $key => $item) {
            if (is_array($item)) {
                unset($items[$key][self::UNTOUCHED_MARKER]);
            }
        }

        return $items;
    }

    /**
     * sha1 van de broncode van Filament\Schemas\Components\Concerns\HasState::hydrateState()
     * (Filament 4.14.0). hydrateState() hierboven gaat ervan uit dat de
     * child-schema's daar vóór callAfterStateHydrated() gehydrateerd worden;
     * de guard-test wordt rood als die methode verandert.
     */
    public const FILAMENT_HYDRATE_HASH = '78386ab37f6302bbe8d05ef1f4898f675d730d55';

    public static function filamentHydrateSource(): string
    {
        $method = new ReflectionMethod(\Filament\Schemas\Components\Component::class, 'hydrateState');
        $lines = file($method->getFileName());

        return implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
    }

    #[ExposedLivewireMethod]
    public function expandItem(string $item): void
    {
        $this->openItem($item);
        $this->partiallyRender();
    }

    #[ExposedLivewireMethod]
    public function collapseItem(string $item): void
    {
        $this->closeItem($item);
        $this->partiallyRender();
    }

    /**
     * Bewust een eigen knop en niet de standaard: alles open is zo zwaar als
     * de builder van Filament zelf (elk blok gerenderd, en bij opslaan
     * gevalideerd en uitgelezen), tot de gebruiker weer inklapt of herlaadt.
     */
    #[ExposedLivewireMethod]
    public function expandAllItems(): void
    {
        foreach (array_keys($this->getRawState() ?? []) as $item) {
            $this->openItem((string) $item);
        }

        $this->partiallyRender();
    }

    #[ExposedLivewireMethod]
    public function collapseAllItems(): void
    {
        session()->forget($this->openItemsSessionKey());
        $this->partiallyRender();
    }

    public function getRawState(): mixed
    {
        return static::normalizeItems(parent::getRawState());
    }

    /**
     * Drop anything that is not a typed block array. Items whose `type` is
     * unknown to this builder are deliberately kept: a block can be hidden
     * (e.g. its Blade view is missing on this site) without its content being
     * silently deleted the next time the record is saved. Keys are preserved
     * because Filament identifies items by their (UUID) key.
     */
    public static function normalizeItems(mixed $state): mixed
    {
        if ($state === null) {
            return null;
        }

        if (! is_array($state)) {
            return [];
        }

        $items = array_filter(
            $state,
            static fn ($item): bool => is_array($item) && filled($item['type'] ?? null),
        );

        return count($items) === count($state) ? $state : $items;
    }

    /**
     * sha1 van de broncode van Filament\Forms\Components\Builder::toEmbeddedHtml()
     * (Filament 4.14.0) waarop de kopie in toEmbeddedHtml() hieronder gebaseerd
     * is. Wijkt de broncode af, dan valt toEmbeddedHtml() terug op de
     * Filament-versie zelf via usesOwnRender().
     */
    public const FILAMENT_RENDER_HASH = '252412fd2edeb63185826091514c6f73ffec4b1a';

    /** @var array<class-string, bool> */
    protected static array $usesOwnRender = [];

    public static function filamentRenderSource(): string
    {
        $method = new ReflectionMethod(Builder::class, 'toEmbeddedHtml');
        $lines = file($method->getFileName());

        return implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1,
        ));
    }

    protected static function expectedFilamentRenderHash(): string
    {
        return self::FILAMENT_RENDER_HASH;
    }

    /**
     * Klopt de hash niet (een klantproject met een andere Filament-versie),
     * dan rendert de builder zoals Filament zelf: trager, maar niet half kapot.
     *
     * Het per-class geheugen hierboven dekt maar één proces: onder PHP-FPM is
     * dat één request, dus zonder extra drempel zou dit bij elke paginalading
     * loggen. `Cache::add()` laat de waarschuwing daarom hooguit één keer per
     * dag per Filament-versie door.
     */
    public static function usesOwnRender(): bool
    {
        return static::$usesOwnRender[static::class] ??= (function (): bool {
            $matches = sha1(static::filamentRenderSource()) === static::expectedFilamentRenderHash();

            if (! $matches) {
                $version = \Composer\InstalledVersions::getVersion('filament/forms') ?? 'unknown';

                if (Cache::add('dashed-content-builder-render-mismatch:' . $version, true, now()->addDay())) {
                    Log::warning('ContentBuilder: Filament Builder::toEmbeddedHtml() wijkt af van de kopie, terugval op Filament.');
                }
            }

            return $matches;
        })();
    }

    /**
     * Kopie van Filament\Forms\Components\Builder::toEmbeddedHtml() (versie
     * 4.14.0). Moet in sync blijven met FILAMENT_RENDER_HASH hierboven;
     * usesOwnRender() bewaakt dat. Afwijkingen van het origineel:
     *  - alleen open items (isItemOpen()) renderen hun velden, een dicht item
     *    krijgt kop en een lege body met een laadtekst;
     *  - in- en uitklappen gaat via expandItem(), collapseItem(),
     *    expandAllItems() en collapseAllItems() op de server
     *    (#[ExposedLivewireMethod]) in plaats van alleen Alpine-state, met een
     *    gedeeltelijke render;
     *  - "Alles uitklappen" en "Alles inklappen" zetten ook de serverstaat
     *    van open items, niet alleen de client-state;
     *  - de blokkiezer tussen elk paar items is vervangen door de
     *    insertBlock-plusknop met één modal, in plaats van een volledige
     *    blokkiezer per tussenruimte;
     *  - een item staat altijd open (isItemOpen() wordt overgeslagen) als de
     *    builder niet collapsible is of geen blokkoppen heeft, want dan is er
     *    geen toggle om het anders nog open te klappen;
     *  - de lus loopt over de ruwe state in plaats van over getItems(), zodat
     *    ongemoeide blokken zonder schema (getUntouchedItems()) een kop
     *    krijgen; label en voorbeeld lezen de ruwe data van het item, en een
     *    item zonder schema rendert altijd dicht.
     */
    public function toEmbeddedHtml(): string
    {
        if (! static::usesOwnRender()) {
            return parent::toEmbeddedHtml();
        }

        $itemSchemas = $this->getItems();
        $blocksByName = collect($this->getBlocks())->keyBy(fn (Block $block): string => $block->getName());

        // Filter before counting so `$itemCount` agrees with the loop's
        // `$isFirst` / `$isLast` calculations. Loopt over de ruwe state, zodat
        // ongemoeide blokken zonder schema ook een kop krijgen.
        $items = array_filter(
            $this->getRawState() ?? [],
            fn ($itemData): bool => is_array($itemData) && $blocksByName->has($itemData['type'] ?? null),
        );

        $existingKeys = array_map('strval', array_keys($items));
        $this->pruneOpenItems($existingKeys);

        $blockPickerBlocks = $this->getBlockPickerBlocks();
        $blockPickerColumns = $this->getBlockPickerColumns();
        $blockPickerWidth = $this->getBlockPickerWidth();
        $hasBlockPreviews = $this->hasBlockPreviews();
        $hasInteractiveBlockPreviews = $this->hasInteractiveBlockPreviews();

        $addAction = $this->getAction($this->getAddActionName());
        $addActionAlignment = $this->getAddActionAlignment();
        $addBetweenAction = $this->getAction($this->getAddBetweenActionName());
        $insertBlockAction = $this->getAction('insertBlock');
        $cloneAction = $this->getAction($this->getCloneActionName());
        $collapseAllAction = $this->getAction($this->getCollapseAllActionName());
        $editAction = $this->getAction($this->getEditActionName());
        $expandAllAction = $this->getAction($this->getExpandAllActionName());
        $deleteAction = $this->getAction($this->getDeleteActionName());
        $moveDownAction = $this->getAction($this->getMoveDownActionName());
        $moveUpAction = $this->getAction($this->getMoveUpActionName());
        $reorderAction = $this->getAction($this->getReorderActionName());
        $extraItemActions = $this->getExtraItemActions();

        $isAddable = $this->isAddable();
        $isCloneable = $this->isCloneable();
        $isCollapsible = $this->isCollapsible();
        $isDeletable = $this->isDeletable();
        $isReorderableWithButtons = $this->isReorderableWithButtons();
        $isReorderableWithDragAndDrop = $this->isReorderableWithDragAndDrop();

        $collapseAllActionIsVisible = $isCollapsible && $collapseAllAction->isVisible();
        $expandAllActionIsVisible = $isCollapsible && $expandAllAction->isVisible();

        $key = $this->getKey();
        $statePath = $this->getStatePath();

        $blockLabelHeadingTag = $this->getHeadingTag();
        $isBlockLabelTruncated = $this->isBlockLabelTruncated();
        $labelBetweenItems = $this->getLabelBetweenItems();

        $id = $this->getId();

        $outerAttributes = (new FilamentComponentAttributeBag())
            ->merge($this->getExtraAttributes(), escape: false)
            ->merge([
                'aria-labelledby' => "{$id}-label",
                'id' => $id,
                'role' => 'group',
            ], escape: false)
            ->class([
                'fi-fo-builder',
                'fi-collapsible' => $isCollapsible,
            ]);

        $itemCount = count($items);
        $itemIndex = 0;

        $hasBlockLabels = $this->hasBlockLabels();
        $hasBlockIcons = $this->hasBlockIcons();
        $hasBlockNumbers = $this->hasBlockNumbers();
        $hasBlockHeaders = $this->hasBlockHeaders();

        ob_start(); ?>

        <div <?= $outerAttributes->toHtml() ?>>
            <?php if ($collapseAllActionIsVisible || $expandAllActionIsVisible) { ?>
                <div
                    <?= (new FilamentComponentAttributeBag())->class([
                        'fi-fo-builder-actions',
                        'fi-hidden' => $itemCount < 2,
                    ])->toHtml() ?>
                >
                    <?php if ($collapseAllActionIsVisible) { ?>
                        <span x-on:click="$dispatch('builder-collapse', '<?= e($statePath) ?>'); $wire.callSchemaComponentMethod(<?= e(Js::from($key)) ?>, 'collapseAllItems')">
                            <?= $collapseAllAction->toHtml() ?>
                        </span>
                    <?php } ?>

                    <?php if ($expandAllActionIsVisible) { ?>
                        <span x-on:click="$dispatch('builder-expand', '<?= e($statePath) ?>'); $wire.callSchemaComponentMethod(<?= e(Js::from($key)) ?>, 'expandAllItems')">
                            <?= $expandAllAction->toHtml() ?>
                        </span>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($itemCount) { ?>
                <ul
                    x-sortable
                    data-sortable-animation-duration="<?= e($this->getReorderAnimationDuration()) ?>"
                    x-on:end.stop="$wire.mountAction('reorder', { items: $event.target.sortable.toArray() }, { schemaComponent: '<?= e($key) ?>' })"
                    class="fi-fo-builder-items"
                >
                    <?php foreach ($items as $itemKey => $item) {
                        /** @var Block $block */
                        $block = $blocksByName->get($item['type']);
                        $itemData = is_array($item['data'] ?? null) ? $item['data'] : [];
                        $itemSchema = $itemSchemas[$itemKey] ?? null;
                        $itemLivewireKey = "{$this->getLivewireKey()}.item.{$itemKey}";

                        $itemIndex++;
                        $isFirst = $itemIndex === 1;
                        $isLast = $itemIndex === $itemCount;
                        // Zonder kop (niet collapsible, of geen blokkoppen) is er geen
                        // toggle om het blok mee te openen: dan altijd renderen, anders
                        // is het blok onbereikbaar.
                        $isItemOpen = (! $isCollapsible) || (! $hasBlockHeaders) || $this->isItemOpen((string) $itemKey);

                        // Open impliceert ontsloten; ontbreekt het schema toch,
                        // dan de dichte weergave in plaats van een fout.
                        $isItemOpen = $isItemOpen && ($itemSchema !== null);

                        $visibleExtraItemActions = array_filter(
                            $extraItemActions,
                            fn (Action $action): bool => $action(['item' => $itemKey])->isVisible(),
                        );
                        $itemCloneAction = $cloneAction(['item' => $itemKey]);
                        $cloneActionIsVisible = $isCloneable && $itemCloneAction->isVisible();
                        $itemDeleteAction = $deleteAction(['item' => $itemKey]);
                        $deleteActionIsVisible = $isDeletable && $itemDeleteAction->isVisible();
                        $itemEditAction = $editAction(['item' => $itemKey]);
                        $editActionIsVisible = $hasBlockPreviews && $itemEditAction->isVisible();
                        $itemMoveDownAction = $moveDownAction(['item' => $itemKey])->disabled($isLast);
                        $moveDownActionIsVisible = $isReorderableWithButtons && $itemMoveDownAction->isVisible();
                        $itemMoveUpAction = $moveUpAction(['item' => $itemKey])->disabled($isFirst);
                        $moveUpActionIsVisible = $isReorderableWithButtons && $itemMoveUpAction->isVisible();
                        $reorderActionIsVisible = $isReorderableWithDragAndDrop && $reorderAction->isVisible();
                        $hasItemHeader = $hasBlockHeaders && ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible || $hasBlockIcons || $hasBlockLabels || $editActionIsVisible || $cloneActionIsVisible || $deleteActionIsVisible || $isCollapsible || $visibleExtraItemActions);
                        ?>

                        <li
                            wire:ignore.self
                            wire:key="<?= e($itemLivewireKey) ?>.item"
                            x-data="{
                                isCollapsed: <?= Js::from(! $isItemOpen) ?>,
                            }"
                            x-on:builder-expand.window="$event.detail === '<?= e($statePath) ?>' && (isCollapsed = false)"
                            x-on:builder-collapse.window="$event.detail === '<?= e($statePath) ?>' && (isCollapsed = true)"
                            x-on:expand="isCollapsed = false"
                            x-sortable-item="<?= e($itemKey) ?>"
                            <?= $block->getExtraAttributeBag()
                                ->class([
                                    'fi-fo-builder-item',
                                    'fi-fo-builder-item-has-header' => $hasItemHeader,
                                ])->toHtml() ?>
                            x-bind:class="{ 'fi-collapsed': isCollapsed }"
                        >
                            <?php if ($hasItemHeader) { ?>
                                <div
                                    <?php if ($isCollapsible) { ?>
                                        x-on:click.stop="isCollapsed = !isCollapsed; $wire.callSchemaComponentMethod(<?= e(Js::from($key)) ?>, isCollapsed ? 'collapseItem' : 'expandItem', { item: <?= e(Js::from((string) $itemKey)) ?> })"
                                    <?php } ?>
                                    class="fi-fo-builder-item-header"
                                >
                                    <?php if ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible) { ?>
                                        <ul class="fi-fo-builder-item-header-start-actions">
                                            <?php if ($reorderActionIsVisible) { ?>
                                                <li x-on:click.stop>
                                                    <?= $reorderAction->extraAttributes(['x-sortable-handle' => true], merge: true)->toHtml() ?>
                                                </li>
                                            <?php } ?>

                                            <?php if ($moveUpActionIsVisible || $moveDownActionIsVisible) { ?>
                                                <li x-on:click.stop><?= $itemMoveUpAction->toHtml() ?></li>
                                                <li x-on:click.stop><?= $itemMoveDownAction->toHtml() ?></li>
                                            <?php } ?>
                                        </ul>
                                    <?php } ?>

                                    <?php
                                        $blockIcon = $block->getIcon();
                                ?>

                                    <?php if ($hasBlockIcons && filled($blockIcon)) { ?>
                                        <?= generate_icon_html($blockIcon, attributes: (new FilamentComponentAttributeBag())->class(['fi-fo-builder-item-header-icon']))?->toHtml() ?>
                                    <?php } ?>

                                    <?php if ($hasBlockLabels) { ?>
                                        <<?= e($blockLabelHeadingTag) ?>
                                            <?= (new FilamentComponentAttributeBag())->class([
                                                'fi-fo-builder-item-header-label',
                                                'fi-truncated' => $isBlockLabelTruncated,
                                            ])->toHtml() ?>
                                        >
                                            <?= e($block->getLabel($itemData, $itemKey, $itemIndex - 1)) ?>
                                            <?php if ($hasBlockNumbers) { ?>
                                                <?= e($itemIndex) ?>
                                            <?php } ?>
                                        </<?= e($blockLabelHeadingTag) ?>>
                                    <?php } ?>

                                    <?php if ($editActionIsVisible || $cloneActionIsVisible || $deleteActionIsVisible || $isCollapsible || $visibleExtraItemActions) { ?>
                                        <ul class="fi-fo-builder-item-header-end-actions">
                                            <?php foreach ($visibleExtraItemActions as $extraItemAction) { ?>
                                                <li x-on:click.stop><?= $extraItemAction(['item' => $itemKey])->toHtml() ?></li>
                                            <?php } ?>

                                            <?php if ($editActionIsVisible) { ?>
                                                <li x-on:click.stop><?= $itemEditAction->toHtml() ?></li>
                                            <?php } ?>

                                            <?php if ($cloneActionIsVisible) { ?>
                                                <li x-on:click.stop><?= $itemCloneAction->toHtml() ?></li>
                                            <?php } ?>

                                            <?php if ($deleteActionIsVisible) { ?>
                                                <li x-on:click.stop><?= $itemDeleteAction->toHtml() ?></li>
                                            <?php } ?>

                                            <?php if ($isCollapsible) { ?>
                                                <li class="fi-fo-builder-item-header-collapsible-actions" x-on:click.stop="isCollapsed = !isCollapsed; $wire.callSchemaComponentMethod(<?= e(Js::from($key)) ?>, isCollapsed ? 'collapseItem' : 'expandItem', { item: <?= e(Js::from((string) $itemKey)) ?> })">
                                                    <div class="fi-fo-builder-item-header-collapse-action">
                                                        <?= $this->getAction('collapse')->toHtml() ?>
                                                    </div>
                                                    <div class="fi-fo-builder-item-header-expand-action">
                                                        <?= $this->getAction('expand')->toHtml() ?>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div
                                x-show="! isCollapsed"
                                <?= (new FilamentComponentAttributeBag())->class([
                                    'fi-fo-builder-item-content',
                                    'fi-fo-builder-item-content-has-preview' => $hasBlockPreviews && $block->hasPreview(),
                                ])->toHtml() ?>
                            >
                                <?php if ($isItemOpen) { ?>
                                    <span wire:key="<?= e($itemLivewireKey) ?>.open" x-init="isCollapsed = false" hidden></span>
                                    <?php if ($hasBlockPreviews && $block->hasPreview()) { ?>
                                        <div
                                            <?= (new FilamentComponentAttributeBag())->class([
                                                'fi-fo-builder-item-preview',
                                                'fi-interactive' => $hasInteractiveBlockPreviews,
                                            ])->toHtml() ?>
                                        >
                                            <?= $block->renderPreview($itemData)->render() ?>
                                        </div>

                                        <?php if ($editActionIsVisible && (! $hasInteractiveBlockPreviews)) { ?>
                                            <div
                                                class="fi-fo-builder-item-preview-edit-overlay"
                                                role="button"
                                                x-on:click.stop="<?= e('$wire.mountAction(\'edit\', { item: \'' . $itemKey . '\' }, { schemaComponent: \'' . $key . '\' })') ?>"
                                            ></div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?= $itemSchema->toHtml() ?>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span wire:key="<?= e($itemLivewireKey) ?>.closed" x-init="isCollapsed = true" hidden></span>
                                    <div class="fi-fo-builder-item-content-loading" style="padding: 0.75rem 1rem; font-size: 0.875rem; opacity: 0.6;">
                                        <?= e(__('Blok laden...')) ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </li>

                        <?php if (! $isLast) { ?>
                            <?php if ($isAddable && $insertBlockAction(['afterItem' => $itemKey])->isVisible()) { ?>
                                <li class="fi-fo-builder-add-between-items-ctn">
                                    <div class="fi-fo-builder-add-between-items">
                                        <?= $insertBlockAction(['afterItem' => (string) $itemKey])->toHtml() ?>
                                    </div>
                                </li>
                            <?php } elseif (filled($labelBetweenItems)) { ?>
                                <li class="fi-fo-builder-label-between-items-ctn">
                                    <div class="fi-fo-builder-label-between-items-divider-before"></div>
                                    <span class="fi-fo-builder-label-between-items"><?= e($labelBetweenItems) ?></span>
                                    <div class="fi-fo-builder-label-between-items-divider-after"></div>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </ul>
            <?php } ?>

            <?php if ($isAddable && $addAction->isVisible()) { ?>
                <?= $this->generateBlockPickerHtml(
                    action: $addAction,
                    blocks: $blockPickerBlocks,
                    key: $key,
                    triggerHtml: $addAction->toHtml(),
                    actionAlignment: $addActionAlignment,
                    columns: $blockPickerColumns,
                    width: $blockPickerWidth,
                ) ?>
            <?php } ?>
        </div>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean(), labelTag: 'div');
    }
}

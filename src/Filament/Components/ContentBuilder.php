<?php

namespace Dashed\DashedCore\Filament\Components;

use ReflectionMethod;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Filament\Actions\Action;
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

        // Alles uitklappen rendert elk blok en brengt precies de bevriezing terug.
        $this->expandAllAction(fn (Action $action): Action => $action->hidden());

        $openLastItem = fn (Action $action): Action => $action->after(
            fn (ContentBuilder $component) => $component->openItem((string) array_key_last($component->getRawState() ?? [])),
        );

        $this->addAction($openLastItem);
        $this->cloneAction($openLastItem);

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
     *  - in- en uitklappen gaat via expandItem(), collapseItem() en
     *    collapseAllItems() op de server (#[ExposedLivewireMethod]) in plaats
     *    van alleen Alpine-state, met een gedeeltelijke render;
     *  - "Alles inklappen" wist ook de serverstaat van open items, niet
     *    alleen de client-state;
     *  - de blokkiezer tussen elk paar items is vervangen door de
     *    insertBlock-plusknop met één modal, in plaats van een volledige
     *    blokkiezer per tussenruimte;
     *  - een item staat altijd open (isItemOpen() wordt overgeslagen) als de
     *    builder niet collapsible is of geen blokkoppen heeft, want dan is er
     *    geen toggle om het anders nog open te klappen.
     */
    public function toEmbeddedHtml(): string
    {
        if (! static::usesOwnRender()) {
            return parent::toEmbeddedHtml();
        }

        $items = $this->getItems();

        // Filter before counting so `$itemCount` agrees with the loop's
        // `$isFirst` / `$isLast` calculations.
        $items = array_filter(
            $items,
            static fn ($item): bool => $item->getParentComponent() instanceof Block,
        );

        $this->pruneOpenItems(array_map('strval', array_keys($items)));

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
                        <span x-on:click="$dispatch('builder-expand', '<?= e($statePath) ?>')">
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
                        $block = $item->getParentComponent();

                        $itemIndex++;
                        $isFirst = $itemIndex === 1;
                        $isLast = $itemIndex === $itemCount;
                        // Zonder kop (niet collapsible, of geen blokkoppen) is er geen
                        // toggle om het blok mee te openen: dan altijd renderen, anders
                        // is het blok onbereikbaar.
                        $isItemOpen = (! $isCollapsible) || (! $hasBlockHeaders) || $this->isItemOpen((string) $itemKey);

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
                            wire:key="<?= e($item->getLivewireKey()) ?>.item"
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
                                            <?= e($block->getLabel($item->getRawState(), $itemKey, $itemIndex - 1)) ?>
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
                                    <span wire:key="<?= e($item->getLivewireKey()) ?>.open" x-init="isCollapsed = false" hidden></span>
                                    <?php if ($hasBlockPreviews && $block->hasPreview()) { ?>
                                        <div
                                            <?= (new FilamentComponentAttributeBag())->class([
                                                'fi-fo-builder-item-preview',
                                                'fi-interactive' => $hasInteractiveBlockPreviews,
                                            ])->toHtml() ?>
                                        >
                                            <?= $block->renderPreview($item->getRawState())->render() ?>
                                        </div>

                                        <?php if ($editActionIsVisible && (! $hasInteractiveBlockPreviews)) { ?>
                                            <div
                                                class="fi-fo-builder-item-preview-edit-overlay"
                                                role="button"
                                                x-on:click.stop="<?= e('$wire.mountAction(\'edit\', { item: \'' . $itemKey . '\' }, { schemaComponent: \'' . $key . '\' })') ?>"
                                            ></div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?= $item->toHtml() ?>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span wire:key="<?= e($item->getLivewireKey()) ?>.closed" x-init="isCollapsed = true" hidden></span>
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

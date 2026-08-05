<?php

namespace Dashed\DashedCore\Filament\Components;

use Filament\Forms\Components\Builder;

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
}

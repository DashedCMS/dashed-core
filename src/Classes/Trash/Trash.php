<?php

namespace Dashed\DashedCore\Classes\Trash;

use Throwable;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * De prullenbak: welke modellen erin vallen, wat erin ligt, en terugzetten of
 * definitief verwijderen. Een model doet alleen mee als het `SoftDeletes`
 * gebruikt; een registratie van een model dat dat (nog) niet doet, wordt
 * genegeerd, zodat de lijst hieronder ook klopt met een ouder pakket.
 *
 * `purge` zegt of het model na `Trash::PURGE_DAYS` dagen vanzelf definitief
 * weg mag. Dat staat alleen aan voor inhoud waar niets anders naar verwijst.
 * Producten, kortingscodes, formulieren en popups blijven staan tot iemand ze
 * met de hand weggooit: bestellingen, inzendingen en vertoningen verwijzen
 * ernaar, en een definitieve verwijdering kan die meenemen.
 */
class Trash
{
    public const PURGE_DAYS = 30;

    /** Zoveel items per soort laat de prullenbak zien. */
    public const LIMIT_PER_TYPE = 200;

    /** @var array<class-string<Model>, array{label: string, purge: bool}> */
    protected static array $types = [];

    /**
     * @param  class-string<Model>  $model
     */
    public static function register(string $model, string $label, bool $purge = false): void
    {
        self::$types[$model] = ['label' => $label, 'purge' => $purge];
    }

    /**
     * De modellen die dashed-core zelf aanmeldt, ook die van andere pakketten
     * (achter class_exists), zodat een klantproject met oudere pakketten toch
     * een volledige prullenbak heeft.
     */
    public static function registerDefaults(): void
    {
        $defaults = [
            \Dashed\DashedPages\Models\Page::class => [__("Pagina's"), true],
            \Dashed\DashedArticles\Models\Article::class => [__('Artikelen'), true],
            \Dashed\DashedMenus\Models\Menu::class => [__("Menu's"), true],
            \Dashed\DashedMenus\Models\MenuItem::class => [__('Menu-items'), true],
            \Dashed\DashedCore\Models\GlobalBlock::class => [__('Globale blokken'), true],
            \Dashed\DashedCore\Models\Redirect::class => [__('Doorverwijzingen'), true],
            \Dashed\DashedEcommerceCore\Models\Product::class => [__('Producten'), false],
            \Dashed\DashedEcommerceCore\Models\ProductGroup::class => [__('Productgroepen'), false],
            \Dashed\DashedEcommerceCore\Models\ProductCategory::class => [__('Categorieën'), false],
            \Dashed\DashedEcommerceCore\Models\DiscountCode::class => [__('Kortingscodes'), false],
            \Dashed\DashedForms\Models\Form::class => [__('Formulieren'), false],
            \Dashed\DashedPopups\Models\Popup::class => [__('Popups'), false],
        ];

        foreach ($defaults as $model => [$label, $purge]) {
            if (class_exists($model)) {
                self::register($model, $label, $purge);
            }
        }
    }

    /**
     * @return array<class-string<Model>, array{label: string, purge: bool}>
     */
    public static function types(): array
    {
        return array_filter(
            self::$types,
            fn (array $type, string $model) => class_exists($model) && in_array(SoftDeletes::class, class_uses_recursive($model), true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public static function handles(Model $model): bool
    {
        return array_key_exists($model::class, self::types());
    }

    /**
     * Alles in de prullenbak, nieuwste eerst.
     *
     * @return Collection<int, array{type: string, label: string, id: int|string, title: string, deleted_at: \Carbon\CarbonInterface, purge: bool}>
     */
    public static function items(?string $type = null): Collection
    {
        $items = collect();

        foreach (self::types() as $model => $meta) {
            if ($type !== null && $type !== $model) {
                continue;
            }

            try {
                $rows = $model::onlyTrashed()->latest($model::make()->getDeletedAtColumn())->limit(self::LIMIT_PER_TYPE)->get();
            } catch (Throwable) {
                continue;
            }

            foreach ($rows as $row) {
                $items->push([
                    'type' => $model,
                    'label' => $meta['label'],
                    'id' => $row->getKey(),
                    'title' => self::title($row),
                    'deleted_at' => $row->{$row->getDeletedAtColumn()},
                    'purge' => $meta['purge'],
                ]);
            }
        }

        return $items->sortByDesc(fn ($item) => $item['deleted_at']?->getTimestamp() ?? 0)->values();
    }

    public static function find(string $type, int|string $id): ?Model
    {
        if (! array_key_exists($type, self::types())) {
            return null;
        }

        return $type::onlyTrashed()->whereKey($id)->first();
    }

    public static function restore(string $type, int|string $id): ?Model
    {
        $model = self::find($type, $id);
        $model?->restore();

        return $model;
    }

    public static function forceDelete(string $type, int|string $id): bool
    {
        $model = self::find($type, $id);

        return $model ? (bool) $model->forceDelete() : false;
    }

    public static function title(Model $model): string
    {
        foreach (['name', 'title', 'from', 'code', 'slug'] as $attribute) {
            $value = rescue(fn () => $model->{$attribute}, null, false);
            if (is_array($value)) {
                $value = reset($value);
            }
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '#'.$model->getKey();
    }

    /** De bewerkpagina van een (teruggezet) record, als die er is. */
    public static function editUrl(Model $model): ?string
    {
        return rescue(function () use ($model) {
            $resource = Filament::getPanel('dashed')->getModelResource($model);

            return $resource && $resource::hasPage('edit') ? $resource::getUrl('edit', ['record' => $model]) : null;
        }, null, false);
    }

    public static function flush(): void
    {
        self::$types = [];
    }
}

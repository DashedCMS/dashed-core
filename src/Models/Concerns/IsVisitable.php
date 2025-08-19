<?php

namespace Dashed\DashedCore\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Dashed\Seo\Traits\HasSeoScore;
use Spatie\Activitylog\LogOptions;
use Dashed\DashedPages\Models\Page;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\View;
use Dashed\DashedCore\Classes\Locales;
use Dashed\Seo\Jobs\ScanSpecificResult;
use Dashed\DashedCore\Classes\UrlHelper;
use Dashed\DashedCore\Models\UrlHistory;
use Spatie\Translatable\HasTranslations;
use Dashed\DashedCore\Models\Customsetting;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dashed\DashedCore\Jobs\ClearContentBlocksCache;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait IsVisitable
{
    use HasMetadata;
    use HasTranslations;
    use HasSearchScope;
    use LogsActivity;
    //    use HasSeoScore;
    use SoftDeletes;
    use HasCustomBlocks;

    public static function bootIsVisitable()
    {
        static::saving(function ($model) {
            foreach (Locales::getLocales() as $locale) {
                $slug = Str::slug($model->getTranslation('slug', $locale['id']) ?: $model->getTranslation('name', $locale['id']));

                while (self::where('id', '!=', $model->id ?? 0)->where('slug->' . $locale['id'], $slug)->count()) {
                    $slug .= Str::random(1);
                }

                $model->setTranslation('slug', $locale['id'], $slug);
            }

            $model->site_ids = $model->site_ids ?? [Sites::getFirstSite()['id']];
        });

        static::saved(function ($model) {
            if (Customsetting::get('seo_check_models', null, false)) {
                ScanSpecificResult::dispatch($model);
            }

            if (self::runHistoryCheck()) {
                Customsetting::set('run_history_check', true);
            }

            foreach (config('dashed-core.blocks.relations', []) as $modelClass => $relation) {
                if ($model instanceof $modelClass) {
                    if ($relation['id'] == '*' || (is_array($relation['id']) && in_array($model->id, $relation['id'])) || $relation['id'] == $model->id) {
                        ClearContentBlocksCache::dispatch($model, $relation['blocks']);
                    }
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public static function runHistoryCheck(): bool
    {
        return true;
    }

    public function scopeThisSite($query, $siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        $query->whereJsonContains('site_ids', $siteId);
    }

    public function scopeIsPublic($query)
    {
        $query->where('public', 1);
    }

    public function scopeSlug($query, string $slug = '')
    {
        if (! $slug) {
            //Should not be found
            $query->where('id', 0);
        } else {
            $query->where('slug->' . app()->getLocale(), $slug);
        }
    }

    public function scopePublicShowable($query)
    {
        if (auth()->guest() || (auth()->check() && auth()->user()->role !== 'admin')) {
            $query->thisSite()
                ->isPublic()
                ->where(function ($query) {
                    $query->where('start_date', null)
                        ->orWhere('start_date', '<=', now()->format('Y-m-d H:i:s'));
                })->where(function ($query) {
                    $query->where('end_date', null)
                        ->orWhere('end_date', '>=', now()->format('Y-m-d H:i:s'));
                });
        }
    }

    public function urlHistory(): MorphOne
    {
        return $this->morphOne(UrlHistory::class, 'model');
    }

    //    public static function getSitemapUrls(Sitemap $sitemap): Sitemap
    //    {
    //        foreach (self::publicShowable()->get() as $model) {
    //            foreach (Locales::getLocales() as $locale) {
    //                if (in_array($locale['id'], Sites::get()['locales'])) {
    //                    Locales::setLocale($locale['id']);
    //                    $url = $model->getUrl($locale['id']);
    //                    //Todo: create another check to see if the page is okay. This is just a quick fix. Maybe do a better check if there is a slug and name available for the item
    //                    if (UrlHelper::checkUrlResponseCode($url) !== 404) {
    //                        $sitemap
    //                            ->add(Url::create($url));
    //                    }
    //                }
    //            }
    //        }
    //
    //        return $sitemap;
    //    }

    public function getStatusAttribute(): bool
    {
        if (! $this->public) {
            return 0;
        } elseif (! $this->start_date && ! $this->end_date) {
            return 1;
        } else {
            if ($this->start_date && $this->end_date) {
                if ($this->start_date <= Carbon::now() && $this->end_date >= Carbon::now()) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                if ($this->start_date) {
                    if ($this->start_date <= Carbon::now()) {
                        return 1;
                    } else {
                        return 0;
                    }
                } else {
                    if ($this->end_date >= Carbon::now()) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            }
        }
    }

    public function breadcrumbs(): array
    {
        $breadcrumbs = [];
        $model = $this;

        $homePage = Page::isHome()->publicShowable()->first();
        if ($homePage) {
            $breadcrumbs[] = [
                'name' => $homePage->name,
                'url' => $homePage->getUrl(),
            ];
        }

        $overviewPage = self::getOverviewPage();
        if ($overviewPage) {
            $breadcrumbs[] = [
                'name' => $overviewPage->name,
                'url' => $overviewPage->getUrl(),
            ];
        }

        if (method_exists($model, 'parent')) {
            $parentBreadcrumbs = [];
            while ($model->parent) {
                if (! $model->parent->is_home) {
                    $parentBreadcrumbs[] = [
                        'name' => $model->parent->name,
                        'url' => $model->parent->getUrl(),
                    ];
                }
                $model = $model->parent;
            }
            if (count($parentBreadcrumbs)) {
                $parentBreadcrumbs = array_reverse($parentBreadcrumbs);
                $breadcrumbs = array_merge($breadcrumbs, $parentBreadcrumbs);
            }
        }

        $breadcrumbs[] = [
            'name' => $this->name,
            'url' => $this->getUrl(),
        ];

        return $breadcrumbs;
    }

    public static function getOverviewPage(): ?Page
    {
        return Page::publicShowable()->find(Customsetting::get(str(class_basename(self::class))->snake()->lower() . '_overview_page_id', Sites::getActive()));
    }

    public function getUrl($activeLocale = null, bool $native = true)
    {
        $originalLocale = app()->getLocale();

        if (! $activeLocale) {
            $activeLocale = $originalLocale;
        }

        $overviewPage = self::getOverviewPage();
        if ($overviewPage) {
            if (method_exists($this, 'parent') && $this->parent) {
                $url = "{$this->parent->getUrl($activeLocale)}/{$this->getTranslation('slug', $activeLocale)}";
            } else {
                $url = "{$overviewPage->getUrl($activeLocale)}/{$this->getTranslation('slug', $activeLocale)}";
            }
        } elseif ($this->is_home) {
            $url = '/';
        } elseif (method_exists($this, 'parent') && $this->parent) {
            $url = "{$this->parent->getUrl($activeLocale)}/{$this->getTranslation('slug', $activeLocale)}";
        } else {
            $url = $this->getTranslation('slug', $activeLocale);
        }

        if (! str($url)->startsWith('/')) {
            $url = '/' . $url;
        }
        if ($activeLocale != Locales::getFirstLocale()['id'] && ! str($url)->startsWith("/{$activeLocale}")) {
            $url = '/' . $activeLocale . $url;
        }

        return $native ? $url : url($url);
    }

    public function getUrlAttribute()
    {
        return $this->getUrl();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public static function getResults($limit = 4, $orderBy = 'created_at', $order = 'DESC', int $exceptId = 0)
    {
        return self::search()->where('id', '!=', $exceptId)->thisSite()->publicShowable()->limit($limit)->orderBy($orderBy, $order)->get();
    }

    public static function getAllResults($pagination = 12, $orderBy = 'created_at', $order = 'DESC')
    {
        return self::search()->thisSite()->publicShowable()->orderBy($orderBy, $order)->paginate($pagination)->withQueryString();
    }

    public static function resolveRoute($parameters = [])
    {
        $class = str(class_basename(self::class))->snake()->lower();
        $className = lcfirst(class_basename(self::class));
        $slug = $parameters['slug'] ?? '';
        $overviewPage = self::getOverviewPage();

        if ($slug) {
            $model = self::resolveModelFromSlug($slug, $overviewPage);
            if (! $model) {
                return null;
            }
        }

        if (isset($model)) {
            return self::prepareRouteResponse($model, $class, $className, $overviewPage);
        }

        return null;
    }

    private static function resolveModelFromSlug($slug, $overviewPage)
    {
        $slugParts = explode('/', $slug);
        if ($overviewPage) {
            $overviewPageUrl = self::getOverviewPageUrl($overviewPage);
            $slugParts = self::removeOverviewPagePartsFromSlug($slugParts, $overviewPageUrl);
        }

        return self::findModelFromSlugParts($slugParts);
    }

    private static function getOverviewPageUrl($overviewPage)
    {
        return Str::of($overviewPage->getUrl(app()->getLocale()))
            ->whenStartsWith('/', fn ($string) => $string->replaceFirst('/', ''))
            ->explode('/')
            ->reject(fn ($part) => in_array($part, collect(Locales::getLocales())->pluck('id')->toArray()))
            ->values();
    }

    private static function removeOverviewPagePartsFromSlug($slugParts, $overviewPageUrl)
    {
        foreach ($overviewPageUrl as $index => $part) {
            if (isset($slugParts[$index]) && $slugParts[$index] === $part) {
                unset($slugParts[$index]);
            } else {
                return [];
            }
        }

        return array_values($slugParts);
    }

    private static function findModelFromSlugParts($slugParts)
    {
        $parentId = null;
        foreach ($slugParts as $slugPart) {
            $query = self::publicShowable()->slug($slugPart);
            if (self::canHaveParent()) {
                $query->where('parent_id', $parentId);
            }
            $model = $query->first();
            if (! $model) {
                return null;
            }
            $parentId = $model->id;
        }

        return $model ?? null;
    }

    private static function prepareRouteResponse($model, $class, $className, $overviewPage)
    {
        $returnForRoute = self::getReturnForRoute($model, $class, $className);
        if (! $returnForRoute) {
            return null;
        }

        self::setSeoMetadata($model);
        self::setAlternateUrls($model);
        self::shareViewData($model, $className, $overviewPage);

        return $returnForRoute;
    }

    private static function getReturnForRoute($model, $class, $className)
    {
        if (method_exists($model, 'returnForRoute')) {
            $returnForRoute = self::returnForRoute();
            if (is_array($returnForRoute)) {
                return array_merge($returnForRoute, [
                    'parameters' => [
                        'model' => $model,
                        $className => $model,
                        'breadcrumbs' => $model->breadcrumbs(),
                    ],
                ]);
            }

            return $returnForRoute;
        }

        $view = env('SITE_THEME', 'dashed') . '.' . str($class)->snake('-')->replace('_', '-') . '.show';

        return View::exists($view) ? view($view) : null;
    }

    private static function setSeoMetadata($model)
    {
        seo()->metaData('metaTitle', $model->metadata->title ?? $model->name);
        seo()->metaData('metaDescription', $model->metadata->description ?? '');
        if ($model->metadata && $model->metadata->image) {
            seo()->metaData('metaImage', $model->metadata->image);
        }
    }

    private static function setAlternateUrls($model)
    {
        $currentLocale = app()->getLocale();
        $alternateUrls = Sites::getLocales()
            ->reject(fn ($locale) => $locale['id'] === $currentLocale)
            ->mapWithKeys(function ($locale) use ($model, $currentLocale) {
                app()->setLocale($locale['id']);
                $url = $model->getUrl();
                app()->setLocale($currentLocale);

                return [$locale['id'] => $url];
            });
        seo()->metaData('alternateUrls', $alternateUrls);
    }

    private static function shareViewData($model, $className, $overviewPage)
    {
        if ($overviewPage) {
            View::share('page', $overviewPage);
        }
        View::share([
            $className => $model,
            'model' => $model,
            'breadcrumbs' => $model->breadcrumbs(),
        ]);
    }

    public function getPlainContent(): string
    {
        $finalString = '';

        if (! is_array($this->content)) {
            return '';
        }

        foreach ($this->content as $item) {
            if (isset($item['data']['content'])) {
                try {
                    $finalString .= strip_tags(cms()->convertToHtml($item['data']['content'])) . ' ';
                } catch (\Exception $e) {
                    if (is_array($item['data']['content']) && ($item['data']['content'][0]['type'] ?? false)) {
                        foreach ($item['data']['content'] as $contentItem) {
                            //                            dump($contentItem['data']['content']);
                            try {
                                if ($contentItem['data']['content'] ?? false) {
                                    $finalString .= strip_tags(cms()->convertToHtml($contentItem['data']['content'])) . ' ';
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    }
                }
            }

            foreach ($item['data'] as $key => $value) {
                if (stripos($key, 'title') !== false && $value) { // Check if "title" exists in the key name
                    $finalString .= strip_tags(cms()->convertToHtml($value)) . ' ';
                }
                //DO NOT USE
                //                // If the value is an array, pass it through the tiptap_editor function
                //                if (is_array($value) && $value) {
                //                    $finalString .= tiptap_converter()->asText($value) . ' ';
                //                }
            }
        }

        return trim($finalString);
    }

    public function getContentBlockCacheKey(int $iteration, string $block, ?string $locale = null): string
    {
        $block = str($block)->slug();
        $locale = $locale ?: app()->getLocale();
        $updatedAt = $this->updated_at->timestamp;

        return "block_{$iteration}_{$block}_{$this->id}_{$updatedAt}_{$locale}";
    }

    public function clearContentBlockCache(array $blocks): void
    {
        foreach (cms()->builder('routeModels') as $routeModel) {
            $routeModel['class']::select('id', 'content', 'updated_at')->chunk(1000, function ($results) use ($routeModel, $blocks) {
                foreach ($results as $result) {
                    foreach (Locales::getLocales() as $locale) {
                        $contentBlocks = $result->getOriginal('content')[$locale['id']] ?? [];
                        $blocksToClear = collect($contentBlocks)->pluck('type')->filter(function ($block) use ($blocks) {
                            return in_array($block, $blocks);
                        })->unique()->toArray();
                        foreach ($blocksToClear as $block) {
                            $block = str($block)->slug()->toString();
                            $iteration = count($contentBlocks);
                            if ($routeModel['class'] == Page::class && $result->id == 2) {
                                while ($iteration > 0) {
                                    cache()->forget($result->getContentBlockCacheKey($iteration, $block, $locale['id']));
                                    $iteration--;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    public function getNameWithParentsAttribute(): string
    {
        $name = $this->name;
        $model = $this;
        while ($model->parent) {
            $name = $model->parent->name . ' > ' . $name;
            $model = $model->parent;
        }

        return $name;
    }

    public static function canHaveParent(): bool
    {
        return true;
    }
}

<?php

namespace Dashed\DashedCore\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
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
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

trait IsVisitable
{
    use HasMetadata;
    use HasTranslations;
    use HasSearchScope;
    use LogsActivity;
    use HasSeoScore;
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

    public static function getSitemapUrls(Sitemap $sitemap): Sitemap
    {
        foreach (self::publicShowable()->get() as $model) {
            foreach (Locales::getLocales() as $locale) {
                if (in_array($locale['id'], Sites::get()['locales'])) {
                    Locales::setLocale($locale['id']);
                    $url = $model->getUrl($locale['id']);
                    //Todo: create another check to see if the page is okay. This is just a quick fix. Maybe do a better check if there is a slug and name available for the item
                    if (UrlHelper::checkUrlResponseCode($url) !== 404) {
                        $sitemap
                            ->add(Url::create($url));
                    }
                }
            }
        }

        return $sitemap;
    }

    public function getStatusAttribute(): bool
    {
        if (! $this->start_date && ! $this->end_date) {
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
        return Page::publicShowable()->find(Customsetting::get(str(class_basename(self::class))->lower() . '_overview_page_id', Sites::getActive()));
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
        $class = str(self::class)->lower()->explode('\\')->last();
        $slug = $parameters['slug'] ?? '';
        if ($slug && $overviewPage = self::getOverviewPage()) {
            $slugParts = explode('/', $slug);
            if ($overviewPage) {
                $unsetCount = 0;
                $overviewPageUrl = str($overviewPage->getUrl())->explode('/');
                $toUnsetCount = $overviewPageUrl->count();
                foreach (Locales::getLocales() as $locale) {
                    foreach ($overviewPageUrl as $part) {
                        if ($part == $locale['id']) {
                            $toUnsetCount--;
                        }
                    }
                }
                while ($toUnsetCount > 1) {
                    unset($slugParts[$unsetCount]);
                    $toUnsetCount--;
                    $unsetCount++;
                }
            }
            $parentId = null;
            foreach ($slugParts as $slugPart) {
                $model = self::publicShowable()->slug($slugPart)->where('parent_id', $parentId)->first();
                $parentId = $model?->id;
                if (! $model) {
                    return;
                }
            }
        }

        if ($model ?? false) {
            if (View::exists(env('SITE_THEME', 'dashed') . '.' . $class . '.show')) {
                seo()->metaData('metaTitle', $model->metadata && $model->metadata->title ? $model->metadata->title : $model->name);
                seo()->metaData('metaDescription', $model->metadata->description ?? '');
                if ($model->metadata && $model->metadata->image) {
                    seo()->metaData('metaImage', $model->metadata->image);
                }

                $correctLocale = app()->getLocale();
                $alternateUrls = [];
                foreach (Sites::getLocales() as $locale) {
                    if ($locale['id'] != $correctLocale) {
                        LaravelLocalization::setLocale($locale['id']);
                        app()->setLocale($locale['id']);
                        $alternateUrls[$locale['id']] = $model->getUrl();
                    }
                }
                LaravelLocalization::setLocale($correctLocale);
                app()->setLocale($correctLocale);
                seo()->metaData('alternateUrls', $alternateUrls);

                if ($overviewPage ?? false) {
                    View::share('page', $overviewPage);
                }
                View::share($class, $model);
                View::share('model', $model);
                View::share('breadcrumbs', $model->breadcrumbs());

                return view(env('SITE_THEME', 'dashed') . '.' . $class . '.show');
            } else {
                return 'pageNotFound';
            }
        }
    }

    public function getPlainContent(): string
    {
        $finalString = '';

        if (! is_array($this->content)) {
            return '';
        }

        foreach ($this->content as $item) {
            // Check if it's content and add it to the string
            if (isset($item['data']['content'])) {
                try {
                    $finalString .= strip_tags(tiptap_converter()->asHTML($item['data']['content'])) . ' ';
                } catch (\Exception $e) {
                    if (is_array($item['data']['content']) && ($item['data']['content'][0]['type'] ?? false)) {
                        foreach ($item['data']['content'] as $contentItem) {
                            //                            dump($contentItem['data']['content']);
                            try {
                                if ($contentItem['data']['content'] ?? false) {
                                    $finalString .= strip_tags(tiptap_converter()->asHTML($contentItem['data']['content'])) . ' ';
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    }
                }
            }

            // Loop through the data to find any keys containing "title"
            foreach ($item['data'] as $key => $value) {
                if (stripos($key, 'title') !== false && $value) { // Check if "title" exists in the key name
                    $finalString .= strip_tags(tiptap_converter()->asHTML($value)) . ' ';
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
}

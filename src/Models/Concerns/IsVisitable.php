<?php

namespace Dashed\DashedCore\Models\Concerns;

use Carbon\Carbon;
use Dashed\DashedArticles\Models\Article;
use Dashed\DashedCore\Classes\UrlHelper;
use Dashed\DashedCore\Jobs\RunUrlHistoryCheck;
use Dashed\DashedCore\Models\Redirect;
use Dashed\DashedCore\Models\UrlHistory;
use Dashed\Seo\Jobs\ScanSpecificResult;
use Dashed\Seo\Traits\HasSeoScore;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Spatie\Activitylog\LogOptions;
use Dashed\DashedPages\Models\Page;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Locales;
use Spatie\Translatable\HasTranslations;
use Dashed\DashedCore\Models\Customsetting;
use Spatie\Activitylog\Traits\LogsActivity;
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
        if (!$siteId) {
            $siteId = Sites::getActive();
        }

        $query->whereJsonContains('site_ids', $siteId);
    }

    public function scopeSlug($query, string $slug = '')
    {
        if (!$slug) {
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
        if (!$this->start_date && !$this->end_date) {
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
                if (!$model->parent->is_home) {
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

    public function getUrl($activeLocale = null)
    {
        $originalLocale = app()->getLocale();

        if (!$activeLocale) {
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

        if (!str($url)->startsWith('/')) {
            $url = '/' . $url;
        }
        if ($activeLocale != Locales::getFirstLocale()['id'] && !str($url)->startsWith("/{$activeLocale}")) {
            $url = '/' . $activeLocale . $url;
        }

        return $url;
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
                unset($slugParts[0]);
            }
            $parentId = null;
            foreach ($slugParts as $slugPart) {
                $model = self::publicShowable()->slug($slugPart)->where('parent_id', $parentId)->first();
                $parentId = $model?->id;
                if (!$model) {
                    return;
                }
            }
        }

        if ($model ?? false) {
            if (View::exists(Customsetting::get('site_theme', null, 'dashed') . '.' . $class . '.show')) {
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

                return view(Customsetting::get('site_theme', null, 'dashed') . '.' . $class . '.show');
            } else {
                return 'pageNotFound';
            }
        }
    }
}

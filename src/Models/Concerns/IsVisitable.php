<?php

namespace Dashed\DashedCore\Models\Concerns;

use Carbon\Carbon;
use Dashed\DashedCore\Jobs\RunUrlHistoryCheck;
use Dashed\DashedCore\Models\Redirect;
use Dashed\DashedCore\Models\UrlHistory;
use Dashed\Seo\Jobs\ScanSpecificResult;
use Dashed\Seo\Traits\HasSeoScore;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
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

    public static function bootIsVisitable()
    {
        static::saved(function ($model) {
            if (Customsetting::get('seo_check_models', null, false)) {
                ScanSpecificResult::dispatch($model);
            }
            RunUrlHistoryCheck::dispatch();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
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

    public function urlHistory()
    {
        return $this->morphMany(UrlHistory::class, 'model');
    }

    public static function getSitemapUrls(Sitemap $sitemap): Sitemap
    {
        foreach (self::publicShowable()->get() as $page) {
            foreach (Locales::getLocales() as $locale) {
                if (in_array($locale['id'], Sites::get()['locales'])) {
                    Locales::setLocale($locale['id']);
                    $sitemap
                        ->add(Url::create($page->getUrl()));
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

    public function getUrl()
    {
        $overviewPage = self::getOverviewPage();
        if ($overviewPage) {
            if (method_exists($this, 'parent') && $this->parent) {
                $url = "{$this->parent->getUrl()}/{$this->slug}";
            } else {
                $url = "{$overviewPage->getUrl()}/{$this->slug}";
            }
        } elseif ($this->is_home) {
            $url = '/';
        } elseif (method_exists($this, 'parent') && $this->parent) {
            $url = "{$this->parent->getUrl()}/{$this->slug}";
        } else {
            $url = $this->slug;
        }

        return LaravelLocalization::localizeUrl($url);
    }

    public function getUrlAttribute()
    {
        return $this->getUrl();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }
}

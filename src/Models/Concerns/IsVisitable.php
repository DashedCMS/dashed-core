<?php

namespace Dashed\DashedCore\Models\Concerns;

use Carbon\Carbon;
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
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

    public function scopeThisSite($query, $siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        $query->whereJsonContains('site_ids', $siteId);
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

    public function scopeSearch($query, ?string $search = null)
    {
        if (request()->get('search') ?: $search) {
            $search = strtolower(request()->get('search') ?: $search);
            $loop = 1;
            foreach (self::getTranslatableAttributes() as $attribute) {
                if ($loop == 1) {
                    $query->whereRaw('LOWER(`' . $attribute . '`) LIKE ? ', ['%' . trim(strtolower($search)) . '%']);
                } else {
                    $query->orWhereRaw('LOWER(`' . $attribute . '`) LIKE ? ', ['%' . trim(strtolower($search)) . '%']);
                }
                $loop++;
            }
        }
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

        $overviewPage = self::getOverviewPage();
        if ($overviewPage) {
            $model = $overviewPage;
        }

        $homePage = Page::isHome()->publicShowable()->first();
        if ($homePage) {
            $breadcrumbs[] = [
                'name' => $homePage->name,
                'url' => $homePage->getUrl(),
            ];
        }

        if (method_exists($model, 'parent')) {
            while ($model->parent) {
                if (! $model->parent->is_home) {
                    $breadcrumbs[] = [
                        'name' => $model->parent->name,
                        'url' => $model->parent->getUrl(),
                    ];
                }
                $model = $model->parent;
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
            $url = "{$overviewPage->getUrl()}/{$this->slug}";
        } elseif ($this->is_home) {
            $url = '/';
        } elseif (method_exists($this, 'parent') && $this->parent) {
            $url = "{$this->parent->getUrl()}/{$this->slug}";
        } else {
            $url = $this->slug;
        }

        return LaravelLocalization::localizeUrl($url);
    }
}

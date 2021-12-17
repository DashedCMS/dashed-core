<?php

namespace Qubiqx\Qcommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Qubiqx\Qcommerce\Classes\Sites;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use SoftDeletes;
    use HasTranslations;
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'qcommerce__menu_items';
    protected $fillable = [
        'menu_id',
        'parent_menu_item_id',
        'site_ids',
        'name',
        'url',
        'type',
        'model',
        'model_id',
        'order',
    ];

    public $translatable = [
        'name',
        'url',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function booted()
    {
        static::created(function ($menuItem) {
            Cache::tags(['menu-items'])->flush();
        });

        static::updated(function ($menuItem) {
            Cache::tags(['menu-items'])->flush();
        });
    }

    public function scopeThisSite($query)
    {
        $query->where('site_ids->' . config('qcommerce.currentSite'), 'active');
    }

    public function scopeSearch($query)
    {
        if (request()->get('search')) {
            $search = strtolower(request()->get('search'));
            $query->where('site_ids', 'LIKE', "%$search%")
                ->orWhere('name', 'LIKE', "%$search%")
                ->orWhere('url', 'LIKE', "%$search%")
                ->orWhere('type', 'LIKE', "%$search%")
                ->orWhere('model', 'LIKE', "%$search%")
                ->orWhere('model_id', 'LIKE', "%$search%");
        }
    }

    public function site()
    {
        foreach (Sites::getSites() as $site) {
            if ($site['id'] == $this->site_id) {
                return $site;
            }
        }
    }

    public function activeSiteIds()
    {
        $menuItem = $this;
        while ($menuItem->parent_menu_item_id) {
            $menuItem = self::find($menuItem->parent_menu_item_id);
            if (! $menuItem) {
                return;
            }
        }

        $sites = [];
        foreach (Sites::getSites() as $site) {
            if (self::where('id', $menuItem->id)->where('site_ids->' . $site['id'], 'active')->count()) {
                array_push($sites, $site['id']);
            }
        }

        return $sites;
    }

    public function siteNames()
    {
        $menuItem = $this;
        while ($menuItem->parent_menu_item_id) {
            $menuItem = self::find($menuItem->parent_menu_item_id);
            if (! $menuItem) {
                return;
            }
        }

        $sites = [];
        foreach (Sites::getSites() as $site) {
            if (self::where('id', $menuItem->id)->where('site_ids->' . $site['id'], 'active')->count()) {
                $sites[$site['name']] = 'active';
            } else {
                $sites[$site['name']] = 'inactive';
            }
        }

        return $sites;
    }

    public function getChilds()
    {
        $childs = [];
        $childMenuItems = self::where('parent_menu_item_id', $this->id)->orderBy('order', 'DESC')->get();
        while ($childMenuItems->count()) {
            $childMenuItemIds = [];
            foreach ($childMenuItems as $childMenuItem) {
                $childMenuItemIds[] = $childMenuItem->id;
                $childs[] = $childMenuItem;
            }
            $childMenuItems = self::whereIn('parent_menu_item_id', $childMenuItemIds)->get();
        }

        return $childs;
    }

    public function getUrl()
    {
        return Cache::tags(['menus', 'menu-items', 'products', 'product-categories', 'pages', 'articles', "menuitem-$this->id"])->remember("menuitem-url-$this->id", 60 * 60 * 24, function () {
            if (! $this->type || $this->type == 'external_url') {
                return LaravelLocalization::localizeUrl($this->url ? $this->url : '/');
            } else {
                $modelResult = $this->model::find($this->model_id);
                if ($modelResult) {
                    $url = $modelResult->getUrl();

                    return $url ?: '/';
                } else {
                    return '/';
                }
            }
        });
    }

    public function name()
    {
        return Cache::tags(['menus', 'menu-items', 'products', 'product-categories', 'pages', 'articles', "menuitem-$this->id"])->remember("menuitem-name-$this->id", 60 * 60 * 24, function () {
            if (! $this->type || $this->type == 'external_url') {
                return $this->name;
            } else {
                $modelResult = $this->model::find($this->model_id);
                $replacementName = '';
                if ($modelResult) {
                    $replacementName = $modelResult->name;
                    if (! $replacementName) {
                        $replacementName = $modelResult->title;
                    }
                }

                return str_replace(':name:', $replacementName, $this->name);
            }
        });
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function childMenuItems()
    {
        return $this->hasMany(self::class, 'parent_menu_item_id')->orderBy('order', 'ASC');
    }

    public function parentMenuItem()
    {
        return $this->belongsTo(self::class, 'parent_menu_item_id');
    }
}

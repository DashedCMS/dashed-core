<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotFoundPage extends Model
{
    use SoftDeletes;
    protected $table = 'dashed__not_found_pages';

    public function occurrences(): HasMany
    {
        return $this->hasMany(NotFoundPageOccurrence::class, 'not_found_page_id');
    }

    public static function saveOccurrence($link, $statusCode, $referer, $userAgent, $ipAddress, $site, $locale): void
    {
        if (request()->has('disableNotFoundLog')) {
            return;
        }

        $notFoundPage = self::withTrashed()->where('link', $link)->where('site', $site)->where('locale', $locale)->first();
        if (! $notFoundPage) {
            $notFoundPage = self::create([
                'link' => $link,
                'site' => $site,
                'locale' => $locale,
            ]);
        }

        $notFoundPage->occurrences()->create([
            'status_code' => $statusCode,
            'referer' => $referer,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);
    }

    public function getHasRedirectAttribute(): bool
    {
        return Redirect::where('from', $this->link)->exists();
    }
}

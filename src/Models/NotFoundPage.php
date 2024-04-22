<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotFoundPage extends Model
{
    protected $table = 'dashed__not_found_pages';

    public function occurrences(): HasMany
    {
        return $this->hasMany(NotFoundPageOccurrence::class, 'not_found_page_id');
    }

    public static function saveOccurrence($link, $statusCode, $referer, $userAgent, $ipAddress, $site, $locale)
    {
        //Todo: create correct resource for this
        //Todo: create a widget to display this data
        //Todo: create function to easily create a redirect
        $notFoundPage = self::firstOrCreate([
            'link' => $link,
            'site' => $site,
            'locale' => $locale
        ]);

        $notFoundPage->occurrences()->create([
            'status_code' => $statusCode,
            'referer' => $referer,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress
        ]);
    }
}

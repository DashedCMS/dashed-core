<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UrlHistory extends Model
{
    protected $table = 'dashed__url_history';

    public static function booted()
    {
        static::saving(function ($urlHistory) {
            $urlHistory->url = str($urlHistory->url)->replace(url('/'), '')->toString();
        });
    }

    public function modelable(): MorphTo
    {
        return $this->morphTo();
    }
}

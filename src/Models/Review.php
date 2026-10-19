<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dashed\DashedCore\Classes\Seo\BusinessProfile;

class Review extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        // De Google-sync doet updateOrCreate op elke rij, elke nacht; saved
        // vuurt ook zonder wijziging en mag dan niet alle sites flushen.
        static::saved(function (Review $review): void {
            if ($review->wasRecentlyCreated || $review->wasChanged(['stars', 'review', 'name'])) {
                BusinessProfile::forget();
            }
        });
        static::deleted(fn () => BusinessProfile::forget());
    }

    protected $table = 'dashed__reviews';
}

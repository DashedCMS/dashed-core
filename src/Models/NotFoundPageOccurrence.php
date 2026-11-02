<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotFoundPageOccurrence extends Model
{
    use SoftDeletes;

    protected $table = 'dashed__not_found_page_occurrences';

    public static function booted()
    {
        static::saved(function ($model) {
            $model->notFoundPage->update([
                'last_occurrence' => $model->created_at,
                'total_occurrences' => $model->notFoundPage->occurrences()->count(),
            ]);
        });
    }

    /**
     * Verwijzers op de eigen site. De host komt uit het lopende verzoek, zodat
     * elke domeinnaam waarop het paneel draait zijn eigen hits herkent.
     */
    public function scopeReferredFromOwnSite($query, string $host)
    {
        return $query->where('referer', 'like', '%://' . $host . '%');
    }

    public function scopeReferredFromElsewhere($query, string $host)
    {
        return $query->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->where('referer', 'not like', '%://' . $host . '%');
    }

    public function notFoundPage(): BelongsTo
    {
        return $this->belongsTo(NotFoundPage::class, 'not_found_page_id')
            ->withTrashed();
    }
}

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

    public function notFoundPage(): BelongsTo
    {
        return $this->belongsTo(NotFoundPage::class, 'not_found_page_id')
            ->withTrashed();
    }
}

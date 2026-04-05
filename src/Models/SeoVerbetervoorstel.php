<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoVerbetervoorstel extends Model
{
    protected $table = 'dashed__seo_verbetervoorstellen';

    protected $casts = [
        'keyword_research' => 'array',
        'field_proposals' => 'array',
        'applied_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'analyzing' => 'Bezig met analyseren...',
            'ready' => 'Klaar voor review',
            'applied' => 'Toegepast',
            'failed' => 'Mislukt',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'analyzing' => 'warning',
            'ready' => 'success',
            'applied' => 'primary',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}

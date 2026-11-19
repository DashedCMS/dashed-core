<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortLinkClick extends Model
{
    protected $table = 'dashed__short_link_clicks';

    public $timestamps = false;

    protected $fillable = ['short_link_id', 'date', 'clicks'];

    protected $casts = [
        'date' => 'date',
        'clicks' => 'integer',
    ];

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }
}

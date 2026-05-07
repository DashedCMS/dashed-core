<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user per-contributor abonnement op de admin samenvatting-mails.
 * Eén rij per (user_id, contributor_key) combinatie. De scheduler-command
 * leest deze tabel uit om elke 15 minuten te bepalen welke samenvattings
 * verstuurd moeten worden.
 */
class SummarySubscription extends Model
{
    protected $table = 'dashed__summary_subscriptions';

    protected $fillable = [
        'user_id',
        'contributor_key',
        'frequency',
        'next_send_at',
        'last_sent_at',
    ];

    protected $casts = [
        'next_send_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Subscriptions die nu verzonden mogen worden, dat wil zeggen alle
     * abonnementen die niet 'off' zijn en waarvan next_send_at leeg is
     * of in het verleden ligt.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('frequency', '!=', 'off')
            ->where(function (Builder $sub) {
                $sub->whereNull('next_send_at')
                    ->orWhere('next_send_at', '<=', now());
            });
    }
}

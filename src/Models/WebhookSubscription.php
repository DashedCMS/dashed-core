<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Een adres dat bij bepaalde gebeurtenissen een ondertekend bericht krijgt.
 * De eigenaar is wie het abonnement heeft (een afnemersprofiel, later
 * bijvoorbeeld een koppeling); dit model weet niets van wat die eigenaar is.
 */
class WebhookSubscription extends Model
{
    protected $table = 'dashed__webhook_subscriptions';

    protected $fillable = [
        'owner_type', 'owner_id', 'url', 'secret', 'events', 'is_active',
        'consecutive_failures', 'last_success_at', 'disabled_at', 'disabled_reason',
    ];

    protected $attributes = [
        'is_active' => true,
        'consecutive_failures' => 0,
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'events' => 'array',
        'is_active' => 'boolean',
        'consecutive_failures' => 'integer',
        'last_success_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    protected $hidden = ['secret'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    /**
     * Ping is er altijd, zodat de testknop werkt zonder dat een pakket hem
     * in zijn eventlijst hoeft te zetten.
     */
    public function listensTo(string $event): bool
    {
        $events = (array) ($this->events ?? []);

        return $event === 'ping'
            || in_array('*', $events, true)
            || in_array($event, $events, true);
    }

    public static function generateSecret(): string
    {
        return 'whsec_' . Str::random(40);
    }
}

<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén tevredenheidsvraag aan een klant: aangemaakt op het moment dat de vraag
 * de deur uit gaat (score nog leeg), ingevuld zodra de klant antwoordt. Zo
 * blijft ook zichtbaar hoeveel vragen onbeantwoord bleven.
 *
 * `subject` is waar de vraag over ging (een chatgesprek, een bestelling),
 * `order_id` en `staff_user_id` staan er los naast zodat de widget en een
 * rapport erop kunnen filteren zonder de polymorfe relatie te volgen.
 */
class SatisfactionRating extends Model
{
    protected $table = 'dashed__satisfaction_ratings';

    protected $guarded = [];

    protected $casts = [
        'score' => 'integer',
        'sent_at' => 'datetime',
        'rated_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class, 'staff_user_id');
    }

    public function scopeRated($query)
    {
        return $query->whereNotNull('score');
    }

    public function isRated(): bool
    {
        return $this->score !== null;
    }
}

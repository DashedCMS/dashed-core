<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ArticleDraft extends Model
{
    protected $table = 'dashed__article_drafts';

    protected $casts = [
        'content_plan' => 'array',
        'article_content' => 'array',
        'applied_at' => 'datetime',
    ];

    public function setProgress(string $message): void
    {
        $this->update(['progress_message' => $message]);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'In wachtrij',
            'planning' => 'Onderzoek & opzet...',
            'writing' => 'Schrijven...',
            'ready' => 'Klaar voor review',
            'applied' => 'Toegepast',
            'failed' => 'Mislukt',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending', 'planning', 'writing' => 'warning',
            'ready' => 'success',
            'applied' => 'primary',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}

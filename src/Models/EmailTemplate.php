<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $table = 'dashed__email_templates';

    protected $fillable = [
        'mailable_key',
        'name',
        'subject',
        'from_name',
        'from_email',
        'blocks',
        'is_active',
    ];

    protected $casts = [
        'blocks' => 'array',
        'is_active' => 'boolean',
    ];

    public static function forMailable(string $mailableClass): ?self
    {
        return static::query()
            ->where('mailable_key', $mailableClass)
            ->where('is_active', true)
            ->first();
    }
}

<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'dashed__roles';

    protected $fillable = ['name', 'extra_permissions'];

    protected $casts = [
        'extra_permissions' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dashed__model_has_roles', 'role_id', 'user_id');
    }
}

<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Een paginasjabloon: een lijst blokken zonder pagina-eigen sleutels. Niet
 * vertaalbaar, want een sjabloon is een opzet en geen inhoud in een taal; bij
 * gebruik komen de blokken in de taal waarin de beheerder werkt.
 *
 * @property string $name
 * @property ?string $description
 * @property ?string $model_type
 * @property ?array $content
 */
class PageTemplate extends Model
{
    protected $table = 'dashed__page_templates';

    protected $fillable = [
        'name',
        'description',
        'model_type',
        'content',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    /** Sjablonen voor dit soort model, plus de sjablonen zonder soort. */
    public function scopeForModel(Builder $query, ?string $class): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('model_type')->when($class, fn (Builder $q) => $q->orWhere('model_type', $class)));
    }

    public function blockCount(): int
    {
        return count($this->content ?? []);
    }
}

<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Support\Carbon;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;
use Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars;

/**
 * Een actiebalk bovenaan de site. Tekst, link en linktekst staan per taal
 * als array (taalcode => waarde); bewust zonder HasTranslations, zodat het
 * formulier per taal een eigen veld kan tonen.
 */
class AnnouncementBar extends Model
{
    protected $table = 'dashed__announcement_bars';

    protected $fillable = [
        'site_id',
        'name',
        'active',
        'text',
        'link_url',
        'link_label',
        'background_color',
        'text_color',
        'starts_at',
        'ends_at',
        'countdown',
        'page_types',
        'dismissible',
        'sort',
    ];

    protected $casts = [
        'active' => 'boolean',
        'text' => 'array',
        'link_url' => 'array',
        'link_label' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'countdown' => 'boolean',
        'page_types' => 'array',
        'dismissible' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnnouncementBar $bar): void {
            $bar->site_id ??= Sites::getActive();
        });

        // De balk zit in de gecachte HTML van elke pagina.
        static::saved(fn (AnnouncementBar $bar) => CacheInvalidator::flushSite($bar->site_id ?: null));
        static::deleted(fn (AnnouncementBar $bar) => CacheInvalidator::flushSite($bar->site_id ?: null));
    }

    public function isLiveAt(Carbon $moment): bool
    {
        return $this->active
            && (! $this->starts_at || $this->starts_at->lte($moment))
            && (! $this->ends_at || $this->ends_at->gt($moment));
    }

    public function textFor(?string $locale = null): string
    {
        return AnnouncementBars::translated($this->text, $locale);
    }

    public function linkUrlFor(?string $locale = null): string
    {
        return AnnouncementBars::translated($this->link_url, $locale);
    }

    public function linkLabelFor(?string $locale = null): string
    {
        return AnnouncementBars::translated($this->link_label, $locale);
    }

    /** Verandert bij elke wijziging, zodat een aangepaste balk weer verschijnt na wegklikken. */
    public function dismissKey(): string
    {
        return 'dab-' . $this->id . '-' . ($this->updated_at?->getTimestamp() ?? 0);
    }
}

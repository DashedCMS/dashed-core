<?php

namespace Dashed\DashedCore\Models;

use Dashed\DashedCore\Classes\Sites;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\ShortLinks\ShortLinks;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een campagnelink: doel op de eigen site plus UTM-velden, bereikbaar via de
 * korte link /k/{code}. Zie ShortLinks voor de regels.
 */
class ShortLink extends Model
{
    protected $table = 'dashed__short_links';

    protected $fillable = [
        'site_id',
        'name',
        'code',
        'destination_url',
        'channel',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    protected $casts = [
        'click_count' => 'integer',
        'last_clicked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ShortLink $link): void {
            $link->site_id ??= Sites::getActive();

            if (blank($link->code)) {
                $link->code = ShortLinks::newCode();
            }
        });
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ShortLinkClick::class);
    }

    /** De doel-URL met de UTM-velden erbij, zoals de redirect hem gebruikt. */
    public function targetUrl(): string
    {
        return ShortLinks::buildTargetUrl($this->absoluteDestination(), $this->utmParameters());
    }

    public function absoluteDestination(): string
    {
        return ShortLinks::absolute((string) $this->destination_url, $this->site_id);
    }

    /** De korte link op het domein van de site, nooit op de Host van het verzoek. */
    public function shortUrl(): string
    {
        return Sites::url('/' . ShortLinks::PREFIX . '/' . $this->code, $this->site_id);
    }

    /** @return array<string, string> */
    public function utmParameters(): array
    {
        $params = [];

        foreach (ShortLinks::UTM_FIELDS as $field) {
            $value = trim((string) $this->{$field});

            if ($value !== '') {
                $params[$field] = $value;
            }
        }

        return $params;
    }
}

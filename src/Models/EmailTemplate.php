<?php

namespace Dashed\DashedCore\Models;

use Dashed\DashedCore\Classes\Locales;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class EmailTemplate extends Model
{
    use HasTranslations;

    protected $table = 'dashed__email_templates';

    public $translatable = ['subject', 'from_name', 'blocks'];

    protected $useFallbackLocale = true;

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

    public function getFallbackLocale(): ?string
    {
        $appFallback = config('app.fallback_locale');
        $subjectTranslations = $this->getTranslations('subject');

        if ($appFallback && filled($subjectTranslations[$appFallback] ?? null)) {
            return $appFallback;
        }

        foreach (Locales::getLocales() as $locale) {
            if (filled($subjectTranslations[$locale['id']] ?? null)) {
                return $locale['id'];
            }
        }

        return $appFallback;
    }

    public static function forMailable(string $mailableClass): ?self
    {
        return static::query()
            ->where('mailable_key', $mailableClass)
            ->where('is_active', true)
            ->first();
    }

    public function missingLocales(): array
    {
        $configured = collect(Locales::getLocales())->pluck('id');

        return $configured
            ->reject(fn ($locale) => $this->hasLocaleFilled($locale))
            ->values()
            ->all();
    }

    public function hasLocaleFilled(string $locale): bool
    {
        $subject = $this->getTranslations('subject')[$locale] ?? null;
        $blocks = $this->getTranslations('blocks')[$locale] ?? null;

        return filled($subject) && ! empty($blocks);
    }
}

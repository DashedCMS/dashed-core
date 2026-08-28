<?php

namespace Dashed\DashedCore\Filament\Columns;

use Dashed\DashedCore\Classes\Locales;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament table column showing how many locales are filled in, the way the
 * email templates overview does. A locale counts as filled when the given
 * attribute (the name or title by default) holds a value in that language;
 * the tooltip names the ones still missing.
 *
 * Pair with any model using spatie/laravel-translatable. Models without
 * getTranslations() simply report nothing missing rather than blowing up, so
 * the column is safe to drop onto a mixed set of resources.
 */
class LocaleStatusColumn
{
    public static function make(string $attribute = 'name', string $name = 'locale_status'): TextColumn
    {
        return TextColumn::make($name)
            ->label(__('Locales'))
            ->badge()
            ->state(fn (Model $record) => self::statusLabel($record, $attribute))
            ->color(fn (Model $record) => self::missingLocales($record, $attribute) === [] ? 'success' : 'warning')
            ->tooltip(function (Model $record) use ($attribute) {
                $missing = self::missingLocales($record, $attribute);

                return $missing === []
                    ? null
                    : 'Ontbrekend: ' . implode(', ', array_map('strtoupper', $missing));
            })
            ->toggleable();
    }

    public static function statusLabel(Model $record, string $attribute = 'name'): string
    {
        $total = count(Locales::getLocales());

        return ($total - count(self::missingLocales($record, $attribute))) . ' / ' . $total;
    }

    /**
     * @return array<int, string>
     */
    public static function missingLocales(Model $record, string $attribute = 'name'): array
    {
        if (! method_exists($record, 'getTranslations')) {
            return [];
        }

        $translations = $record->getTranslations($attribute);

        return collect(Locales::getLocales())
            ->map(fn ($locale) => is_array($locale) ? ($locale['id'] ?? null) : $locale)
            ->filter()
            ->reject(fn (string $locale) => filled($translations[$locale] ?? null))
            ->values()
            ->all();
    }
}

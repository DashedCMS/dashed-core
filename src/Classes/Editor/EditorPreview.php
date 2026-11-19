<?php

namespace Dashed\DashedCore\Classes\Editor;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Filament\Components\ContentBuilder;

/**
 * Voorvertoning van de onopgeslagen staat van een bewerkpagina. De
 * formulierstaat gaat voor dertig minuten in de cache onder een token per
 * beheerder, record en taal; de route rendert die staat bovenop een verse
 * kopie van het record, zonder iets op te slaan. Een model doet mee door
 * `renderEditorPreview()` te hebben (Page, Article).
 */
class EditorPreview
{
    public const TTL_MINUTES = 30;

    public const ROUTE = 'dashed.editor.preview';

    public static function supports(?Model $record): bool
    {
        return $record !== null && $record->exists && method_exists($record, 'renderEditorPreview');
    }

    /**
     * Bewaart de staat en geeft het token terug. Het token is stabiel per
     * beheerder, record en taal, zodat een tweede klik dezelfde URL houdt.
     */
    public static function store(Model $record, array $state, string $locale, int|string|null $userId = null): string
    {
        $userId ??= auth()->id();
        $token = substr(hash_hmac('sha256', implode('|', [$userId, $record::class, $record->getKey(), $locale]), (string) config('app.key')), 0, 40);

        Cache::put(static::cacheKey($token), [
            'class' => $record::class,
            'id' => $record->getKey(),
            'locale' => $locale,
            'user_id' => $userId,
            'attributes' => static::attributesFromState($record, $state),
        ], now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    public static function url(string $token): string
    {
        return URL::temporarySignedRoute(self::ROUTE, now()->addMinutes(self::TTL_MINUTES), ['token' => $token]);
    }

    public static function cacheKey(string $token): string
    {
        return 'dashed:editor-preview:'.$token;
    }

    /**
     * @return array{class: string, id: mixed, locale: string, user_id: mixed, attributes: array<string, mixed>}|null
     */
    public static function load(string $token): ?array
    {
        $payload = Cache::get(static::cacheKey($token));

        return is_array($payload) ? $payload : null;
    }

    /**
     * Alleen velden die echt op het model staan: vertaalbare velden en
     * bestaande kolommen. Relaties (metadata, custom blocks) blijven die van
     * het opgeslagen record.
     *
     * @return array<string, mixed>
     */
    public static function attributesFromState(Model $record, array $state): array
    {
        $translatable = $record->translatable ?? [];
        $columns = array_keys($record->getAttributes());
        $attributes = [];

        foreach ($state as $key => $value) {
            if (! is_string($key) || in_array($key, [$record->getKeyName(), 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            if (! in_array($key, $translatable, true) && ! in_array($key, $columns, true)) {
                continue;
            }

            if ($key === 'content' || is_array($value)) {
                $value = TemplateBlocks::stripUuidKeys(is_array($value) ? ContentBuilder::withoutUntouchedMarkers($value) : []);
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    /**
     * Een verse kopie van het record met de bewaarde staat erop. Alleen in
     * het geheugen: de controller roept nergens save() aan.
     */
    public static function hydrate(array $payload): ?Model
    {
        $class = $payload['class'] ?? null;
        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        /** @var Model|null $model */
        $model = $class::query()->find($payload['id'] ?? null);
        if (! $model) {
            return null;
        }

        $translatable = $model->translatable ?? [];
        $locale = (string) ($payload['locale'] ?? app()->getLocale());

        foreach (($payload['attributes'] ?? []) as $key => $value) {
            if (in_array($key, $translatable, true)) {
                $model->setTranslation($key, $locale, $value);
            } else {
                $model->setAttribute($key, $value);
            }
        }

        return $model;
    }
}

<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * De taal van de beheeromgeving, los van de taal van de inhoud. Alleen de
 * translator wisselt; app()->getLocale() en config('app.locale') blijven de
 * inhoudstaal, want orders, slugs, Translation::get en spatie-translatable
 * leunen daarop. Zie de spec 2026-10-01-admin-engels-design.md.
 */
class AdminLocale
{
    public static function default(): string
    {
        $default = (string) config('dashed-core.admin_locale', 'nl');

        return array_key_exists($default, static::options()) ? $default : 'nl';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return (array) config('dashed-core.admin_locales', ['nl' => 'Nederlands', 'en' => 'English']);
    }

    public static function for(?Authenticatable $user): string
    {
        $keuze = $user?->admin_locale ?? null;

        return is_string($keuze) && array_key_exists($keuze, static::options())
            ? $keuze
            : static::default();
    }

    public static function apply(?Authenticatable $user): void
    {
        app('translator')->setLocale(static::for($user));
    }

    /**
     * Voert $callback uit met de translator op de inhoudstaal en zet daarna
     * terug wat er stond. Voor alles wat een klant of collega te zien krijgt
     * terwijl een beheerder het opbouwt: mails vooral.
     */
    public static function asContent(callable $callback): mixed
    {
        $translator = app('translator');
        $ui = $translator->getLocale();
        $content = app()->getLocale();

        if ($ui === $content) {
            return $callback();
        }

        $translator->setLocale($content);

        try {
            return $callback();
        } finally {
            $translator->setLocale($ui);
        }
    }
}

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
     * Hoe diep we nu in asContent() zitten. Zolang dit boven nul staat volgt
     * de translator gewoon de app-taal, ook bij een geneste wissel (zoals
     * EmailRenderer die naar de taal van de order springt). Statisch, dus
     * resetContentDepth() bij elke boot: anders lekt een afgebroken stand
     * van de ene test naar de volgende.
     */
    protected static int $contentDepth = 0;

    /**
     * Voert $callback uit met de translator op de inhoudstaal en zet daarna
     * terug wat er stond. Voor alles wat een klant of collega te zien krijgt
     * terwijl een beheerder het opbouwt: mails vooral.
     */
    public static function asContent(callable $callback): mixed
    {
        $translator = app('translator');
        $ui = $translator->getLocale();

        $translator->setLocale(app()->getLocale());
        static::$contentDepth++;

        try {
            return $callback();
        } finally {
            static::$contentDepth--;
            $translator->setLocale($ui);
        }
    }

    public static function resetContentDepth(): void
    {
        static::$contentDepth = 0;
    }

    /**
     * Luistert op LocaleUpdated. App::setLocale() zet de translator altijd
     * mee, en code die de app-taal even wisselt en terugzet (getUrl() per
     * tabelrij, alternate URLs, Translation::get in een andere taal) laat de
     * translator dan op de inhoudstaal staan: de rest van het verzoek wordt
     * Nederlands. In het paneel zetten we hem daarom na elke wissel terug op
     * de taal van de beheerder. Niet binnen asContent(), daar hoort de
     * translator de inhoud te volgen, en niet op de front-end.
     */
    public static function reapplyAfterLocaleChange(): void
    {
        if (static::$contentDepth > 0 || ! \Filament\Facades\Filament::isServing()) {
            return;
        }

        static::apply(auth()->guard('web')->user());
    }
}

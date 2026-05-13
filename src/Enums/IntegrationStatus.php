<?php

namespace Dashed\DashedCore\Enums;

/**
 * Status of a registered admin integration, surfaced as the status-dot
 * colour on the IntegrationsDashboard.
 */
enum IntegrationStatus: string
{
    case Connected = 'connected';
    case Misconfigured = 'misconfigured';
    case Failing = 'failing';
    case Disabled = 'disabled';

    public function dotColor(): string
    {
        return match ($this) {
            self::Connected => 'bg-emerald-500',
            self::Misconfigured => 'bg-amber-500',
            self::Failing => 'bg-rose-500',
            self::Disabled => 'bg-zinc-400',
        };
    }

    /**
     * Hex colour for the status dot. Inline styles bypass Tailwind JIT —
     * package blade views can't rely on arbitrary Tailwind classes being
     * picked up by the host app's CSS build.
     */
    public function dotHex(): string
    {
        return match ($this) {
            self::Connected => '#10b981',     // emerald-500
            self::Misconfigured => '#f59e0b', // amber-500
            self::Failing => '#f43f5e',       // rose-500
            self::Disabled => '#a1a1aa',      // zinc-400
        };
    }

    /**
     * Hex colour for the 4px left card border + banner border.
     */
    public function borderHex(): string
    {
        return $this->dotHex();
    }

    /**
     * Tinted background hex (10% opacity feel) for cards/banners.
     */
    public function bgTintHex(): string
    {
        return match ($this) {
            self::Connected => '#ecfdf5',     // emerald-50
            self::Misconfigured => '#fffbeb', // amber-50
            self::Failing => '#fff1f2',       // rose-50
            self::Disabled => '#f4f4f5',      // zinc-100
        };
    }

    /**
     * Text colour hex for the status pill.
     */
    public function pillTextHex(): string
    {
        return match ($this) {
            self::Connected => '#047857',     // emerald-700
            self::Misconfigured => '#b45309', // amber-700
            self::Failing => '#be123c',       // rose-700
            self::Disabled => '#3f3f46',      // zinc-700
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Verbonden',
            self::Misconfigured => 'Onvolledig geconfigureerd',
            self::Failing => 'Werkt niet',
            self::Disabled => 'Uitgeschakeld',
        };
    }

    /**
     * Returns the Tailwind palette name used by the status across the dashboard
     * (border, pill background, ring). One source of truth so other components
     * (settings-page banner, widget) stay in sync.
     */
    public function statusColor(): string
    {
        return match ($this) {
            self::Connected => 'green',
            self::Misconfigured => 'amber',
            self::Failing => 'red',
            self::Disabled => 'gray',
        };
    }

    /**
     * 4px left border colour class used on the integration card.
     */
    public function borderClass(): string
    {
        return match ($this) {
            self::Connected => 'border-l-4 border-emerald-500',
            self::Misconfigured => 'border-l-4 border-amber-500',
            self::Failing => 'border-l-4 border-rose-500',
            self::Disabled => 'border-l-4 border-zinc-400',
        };
    }

    /**
     * Pill classes (background + text) shown in the top-right of the
     * integration card and in the settings-page banner.
     */
    public function pillClasses(): string
    {
        return match ($this) {
            self::Connected => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-400/30',
            self::Misconfigured => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/30',
            self::Failing => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/30',
            self::Disabled => 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-600/20 dark:bg-zinc-500/10 dark:text-zinc-300 dark:ring-zinc-400/30',
        };
    }

    /**
     * Filament colour name (used for filament::section / button colour props).
     */
    public function filamentColor(): string
    {
        return match ($this) {
            self::Connected => 'success',
            self::Misconfigured => 'warning',
            self::Failing => 'danger',
            self::Disabled => 'gray',
        };
    }
}

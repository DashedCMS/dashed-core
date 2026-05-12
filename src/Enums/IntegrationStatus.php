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

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Verbonden',
            self::Misconfigured => 'Onvolledig geconfigureerd',
            self::Failing => 'Werkt niet',
            self::Disabled => 'Uitgeschakeld',
        };
    }
}

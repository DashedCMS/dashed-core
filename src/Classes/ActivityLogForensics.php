<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * IP-adres en user-agent op elke regel in het activiteitenlogboek, zodat een
 * wijziging achteraf aan een verzoek te koppelen is. In de console (cron,
 * wachtrij) is er geen verzoek en blijft de regel zoals hij was; in tests
 * wel, want daar is het verzoek nagebootst en juist wat getoetst wordt.
 */
class ActivityLogForensics
{
    public const USER_AGENT_MAX_LENGTH = 200;

    public static function register(): void
    {
        Activity::creating(function (Activity $activity): void {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $activity->properties = $activity->properties->merge([
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), self::USER_AGENT_MAX_LENGTH, ''),
            ]);
        });
    }
}

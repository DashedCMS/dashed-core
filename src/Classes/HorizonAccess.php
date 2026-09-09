<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Wie /horizon mag openen. Eerst de IP-lijst van het CMS (dezelfde als voor
 * het paneel: wie het CMS op adres afsluit bedoelt de wachtrijmonitor ook),
 * dan de gate `viewHorizon` van het project als die gedefinieerd is, en
 * anders een superadmin of iemand met het recht "Horizon bekijken".
 */
class HorizonAccess
{
    public static function allows(Request $request): bool
    {
        if (! CmsIpAllowlist::allows($request->ip())) {
            return false;
        }

        $user = $request->user();

        if (! $user) {
            return false;
        }

        if (Gate::has('viewHorizon')) {
            return Gate::forUser($user)->check('viewHorizon');
        }

        return ($user->role ?? null) === 'superadmin' || $user->can('view_horizon');
    }
}

<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Str;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

/**
 * Zet een account per direct buiten werking: willekeurig wachtwoord van 64
 * tekens, nieuw remember-token, superadmin uit, alle rollen los, en een regel in
 * het activiteitenlogboek met de staat van daarvoor. Gedeeld door het
 * commando dashed:lock-user en de "dit was ik niet"-link in de loginmail,
 * zodat beide precies hetzelfde doen.
 *
 * Het nieuwe wachtwoord is wat lopende sessies afbreekt: Laravel's
 * AuthenticateSession vergelijkt de wachtwoordhash per verzoek. Herstel
 * daarna is met de hand: een superadmin zet een nieuw wachtwoord, de
 * gebruiker stelt MFA opnieuw in en krijgt zijn rollen terug.
 */
class LockUserAction
{
    public const NOT_ME_LINK_DAYS = 7;

    /**
     * @return array{is_superadmin: bool, roles: array<int, string>} de staat van voor de vergrendeling
     */
    public function handle(User $user, string $reason): array
    {
        $before = ['is_superadmin' => $user->isSuperadmin(), 'roles' => $user->roles()->pluck('name')->all()];

        $user->password = Hash::make(Str::random(64));
        $user->remember_token = Str::random(60);
        $user->is_superadmin = false;
        $user->save();
        $user->roles()->detach();

        // Een nieuw wachtwoord breekt sessies af, maar geen API-sleutels: die
        // blijven geldig tot ze weg zijn. Dus weg ermee, van de app en van
        // de afnemers-API tegelijk.
        $user->tokens()->delete();

        rescue(fn () => activity()
            ->performedOn($user)
            ->withProperties(['reason' => $reason, 'before' => $before])
            ->log('security:lock-user'), report: false);

        return $before;
    }

    /**
     * De ondertekende link voor in de loginmail, zeven dagen geldig. De host
     * komt van de site en niet van het verzoek, om dezelfde reden als bij
     * de wachtwoord-reset (zie TrustedHosts).
     */
    public static function notMeUrl(User $user): string
    {
        return Sites::withForcedRootUrl(fn () => URL::temporarySignedRoute(
            'dashed.security.not-me',
            now()->addDays(self::NOT_ME_LINK_DAYS),
            ['user' => $user->getKey()],
        ));
    }
}

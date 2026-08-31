<?php

namespace Dashed\DashedCore\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Models\LoginAttempt;
use Dashed\DashedCore\Classes\MfaFreshness;
use Illuminate\Validation\ValidationException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;

/**
 * Filament's inlogpagina, plus twee dingen. Zodra het inloggen slaagt voor
 * een gebruiker met MFA, staat het tijdstip van die geslaagde code in de
 * sessie; Filament logt pas in nadat de code goed is, dus een geslaagde login
 * van een gebruiker met MFA betekent altijd dat de code zojuist is ingevoerd.
 * En elke uitkomst gaat het inloglogboek in (LoginAttempt), hier en niet via
 * de algemene auth-events, omdat de front-end dezelfde guard deelt.
 */
class CmsLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $email = $this->data['email'] ?? null;
        $inMfaPhase = filled($this->userUndertakingMultiFactorAuthentication);

        try {
            $response = parent::authenticate();
        } catch (ValidationException $exception) {
            // Filament meldt zowel een fout wachtwoord als een foute MFA-code
            // als ValidationException; welke fase het was weten we van vóór
            // de aanroep.
            LoginAttempt::record($inMfaPhase ? LoginAttempt::RESULT_FAILED_MFA : LoginAttempt::RESULT_FAILED, $email);

            throw $exception;
        }

        if ($response) {
            $user = Filament::auth()->user();

            MfaFreshness::stampIfUserHasMfa($user);
            LoginAttempt::record(LoginAttempt::RESULT_SUCCESS, $user?->email ?? $email, $user instanceof User ? $user : null);
        }

        return $response;
    }
}

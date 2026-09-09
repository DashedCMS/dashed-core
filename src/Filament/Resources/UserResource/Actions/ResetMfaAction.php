<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Actions;

use Filament\Actions\Action;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Mail\MfaResetMail;

/**
 * De MFA van een collega opnieuw laten instellen: wist het app-geheim, de
 * herstelcodes en de e-mailmethode. Omdat MFA verplicht is, krijgt de
 * gebruiker bij zijn eerstvolgende paginalading de instelpagina, en zijn
 * lopende sessies kunnen daar niet omheen (EnsureMfaIsSetUp leest per verzoek).
 *
 * Alleen voor superadmins, en nooit op jezelf: je eigen MFA regel je via het
 * profiel, en een superadmin die zichzelf per ongeluk zou resetten heeft dan
 * niets meer om mee in te loggen behalve wachtwoord plus opnieuw instellen.
 */
class ResetMfaAction
{
    public static function make(string $name = 'resetMfa'): Action
    {
        return Action::make($name)
            ->label(__('MFA resetten'))
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->visible(fn (User $record): bool => static::canReset($record))
            ->requiresConfirmation()
            ->modalHeading(__('MFA van deze gebruiker resetten'))
            ->modalDescription(fn (User $record) => __('De authenticator-koppeling, de herstelcodes en de e-mailmethode van :email worden gewist. Bij de eerstvolgende paginalading moet deze gebruiker MFA opnieuw instellen. De gebruiker krijgt hier een e-mail over.', ['email' => $record->email]))
            ->modalSubmitActionLabel(__('MFA resetten'))
            ->action(function (User $record): void {
                static::reset($record);

                Notification::make()
                    ->title(__('MFA gereset'))
                    ->body(__(':email moet MFA bij de volgende paginalading opnieuw instellen.', ['email' => $record->email]))
                    ->success()
                    ->send();
            });
    }

    public static function canReset(User $record): bool
    {
        $actor = auth()->user();

        return $actor
            && ($actor->role ?? null) === 'superadmin'
            && (int) $actor->getKey() !== (int) $record->getKey()
            && static::hasMfa($record);
    }

    public static function hasMfa(User $record): bool
    {
        return filled($record->app_authentication_secret) || (bool) $record->has_email_authentication;
    }

    public static function reset(User $record): void
    {
        $record->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
            'has_email_authentication' => false,
        ])->save();

        $actor = auth()->user();

        if (function_exists('activity')) {
            activity()
                ->performedOn($record)
                ->causedBy($actor)
                ->log('MFA gereset door ' . ($actor?->email ?? 'onbekend'));
        }

        if ($record->email) {
            Mail::to($record->email)->queue(new MfaResetMail(
                name: trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: (string) ($record->name ?? ''),
                actor: (string) ($actor?->email ?? ''),
                at: now()->format('d-m-Y H:i'),
            ));
        }
    }
}

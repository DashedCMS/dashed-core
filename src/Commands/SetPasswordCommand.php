<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Nieuw wachtwoord voor een account vanaf de server, voor als de reset per
 * mail voor beheerders uitstaat (CmsPasswordReset) of een account is
 * vergrendeld (LockUserAction). Zonder --password een willekeurig
 * wachtwoord, dat eenmalig wordt getoond.
 */
class SetPasswordCommand extends Command
{
    protected $signature = 'dashed:set-password {user : id of e-mailadres} {--password= : Het nieuwe wachtwoord; leeg = willekeurig}';

    protected $description = 'Zet een nieuw wachtwoord voor een account en maakt lopende sessies ongeldig.';

    public function handle(): int
    {
        $identifier = (string) $this->argument('user');
        $user = is_numeric($identifier)
            ? User::find((int) $identifier)
            : User::query()->whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first();

        if (! $user) {
            $this->error("Gebruiker '{$identifier}' niet gevonden.");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(20));

        $user->password = Hash::make($password);
        $user->remember_token = Str::random(60);
        $user->save();

        rescue(fn () => activity()->performedOn($user)->log('security:set-password'), report: false);

        $this->info("Wachtwoord van #{$user->id} ({$user->email}) is vervangen; lopende sessies zijn ongeldig.");

        if (! $this->option('password')) {
            $this->line('Nieuw wachtwoord: ' . $password);
        }

        return self::SUCCESS;
    }
}

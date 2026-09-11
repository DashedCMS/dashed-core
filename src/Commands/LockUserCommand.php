<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\User;
use Dashed\DashedCore\Classes\LockUserAction;

class LockUserCommand extends Command
{
    protected $signature = 'dashed:lock-user {user : id of e-mailadres} {--reason= : Waarom (komt in het activiteitenlogboek)}';

    protected $description = 'Zet een account direct buiten werking: nieuw wachtwoord, nieuw remember-token, rol klant, alle rollen los.';

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

        $before = app(LockUserAction::class)->handle($user, (string) ($this->option('reason') ?: 'dashed:lock-user'));

        $this->info("Gebruiker #{$user->id} ({$user->email}) is vergrendeld: wachtwoord en remember-token vervangen, rol {$before['role']} naar customer, rollen losgekoppeld.");
        $this->line('Herstel: een superadmin zet een nieuw wachtwoord (dashed:set-password), de gebruiker stelt MFA opnieuw in en krijgt zijn rol terug.');

        return self::SUCCESS;
    }
}

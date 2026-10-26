<?php

namespace Dashed\DashedCore\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Classes\Mails;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Enums\IntegrationStatus;
use Dashed\DashedCore\Notifications\AdminNotifier;
use Dashed\DashedCore\Integrations\IntegrationHealth;
use Dashed\DashedCore\Integrations\IntegrationRegistry;
use Dashed\DashedCore\Mail\IntegrationStatusChangedMail;
use Dashed\DashedCore\Integrations\IntegrationDefinition;
use Dashed\DashedCore\Integrations\IntegrationHealthRunner;

/**
 * Polt elke geregistreerde koppeling per site en mailt de admins zodra
 * een koppeling van status verandert (eenmalig per transitie - geen
 * herhaalde spam zolang de status hetzelfde blijft). Bedoeld als cron-
 * fallback bovenop de live health-checks die de IntegrationsDashboard al
 * uitvoert.
 */
class CheckIntegrationsHealth extends Command
{
    protected $signature = 'dashed:check-integrations-health';

    protected $description = 'Controleer de status van alle geregistreerde koppelingen en mail admins bij wijzigingen.';

    public function handle(): int
    {
        $registry = app(IntegrationRegistry::class);
        $runner = app(IntegrationHealthRunner::class);
        $sites = Sites::getSites();

        if (empty($sites)) {
            // Single-site / geen sites geconfigureerd: behandel het als null-site.
            $sites = [['id' => null]];
        }

        foreach ($registry->all() as $def) {
            foreach ($sites as $site) {
                $siteId = $site['id'] ?? null;

                try {
                    // Cache invalideren zodat we live status zien, niet de
                    // 5-min cache van het dashboard.
                    $runner->forget($def->slug, $siteId);
                    $health = $runner->run($def, $siteId);
                    $this->processResult($def, $siteId, $health);
                    $this->processExpiry($def, $siteId, $health);
                } catch (Throwable $e) {
                    Log::warning('integration-health: probe failed', [
                        'slug' => $def->slug,
                        'site_id' => $siteId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return self::SUCCESS;
    }

    protected function processResult(IntegrationDefinition $def, ?string $siteId, IntegrationHealth $health): void
    {
        $key = 'integration_' . $def->slug . '_last_known_status';
        $previous = Customsetting::get($key, $siteId, null);
        $current = $health->status->value;

        // Eerste run, of legacy install zonder eerdere status: alleen
        // opslaan, niet mailen. Anders krijg je elke nieuwe integratie
        // een mail bij de eerste poll.
        if ($previous === null || $previous === '') {
            Customsetting::set($key, $current, $siteId);

            return;
        }

        if ($previous === $current) {
            return;
        }

        Customsetting::set($key, $current, $siteId);

        $wasConnected = $previous === IntegrationStatus::Connected->value;
        $nowConnected = $current === IntegrationStatus::Connected->value;

        // Alleen mailen op echte "broken now" of "recovered" transities.
        // misconfigured -> failing en omgekeerd zijn geen interessante
        // notificaties voor admins (al "stuk") en zouden alleen ruis zijn.
        if (! $wasConnected && ! $nowConnected) {
            return;
        }

        // Disabled betekent expliciet uitgezet door admin - ook geen mail.
        if ($current === IntegrationStatus::Disabled->value) {
            return;
        }

        // De bel krijgt dezelfde transities als de mail, ook als er geen
        // mailadressen zijn ingesteld.
        $this->notifyAdmins(
            $nowConnected
                ? __(':koppeling werkt weer', ['koppeling' => $def->label])
                : __(':koppeling werkt niet meer', ['koppeling' => $def->label]),
            $health->message,
            $nowConnected ? 'success' : 'danger',
        );

        $recipients = Mails::getAdminNotificationEmails();
        if (empty($recipients)) {
            return;
        }

        $mail = new IntegrationStatusChangedMail(
            slug: $def->slug,
            label: $def->label,
            oldStatus: $previous,
            newStatus: $current,
            message: $health->message,
            siteId: $siteId,
        );

        foreach ($recipients as $email) {
            try {
                AdminNotifier::send($mail, $email);
            } catch (Throwable $e) {
                Log::warning('integration-health: mail kon niet verzonden worden', [
                    'slug' => $def->slug,
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Toegang die vanzelf verloopt (een refresh-token van Exact): één melding
     * bij veertien dagen en één bij drie dagen, en één als hij verlopen is.
     * Per vervaldatum en drempel één keer, zodat een vernieuwd token weer
     * opnieuw meetelt.
     */
    protected function processExpiry(IntegrationDefinition $def, ?string $siteId, IntegrationHealth $health): void
    {
        $days = $health->daysUntilExpiry();

        if ($days === null) {
            return;
        }

        $threshold = collect([0, 3, 14])->first(fn (int $limit) => $days <= $limit);

        if ($threshold === null) {
            return;
        }

        $key = 'integration_' . $def->slug . '_expiry_notified';
        $marker = $health->expiresAt->toDateString() . ':' . $threshold;

        if (Customsetting::get($key, $siteId) === $marker) {
            return;
        }

        Customsetting::set($key, $marker, $siteId);

        $title = $days < 0
            ? __('De toegang van :koppeling is verlopen', ['koppeling' => $def->label])
            : trans_choice('De toegang van :koppeling verloopt over :dagen dag|De toegang van :koppeling verloopt over :dagen dagen', max(0, $days), ['koppeling' => $def->label, 'dagen' => max(0, $days)]);

        $this->notifyAdmins($title, __('Koppel :koppeling opnieuw bij Instellingen om te voorkomen dat de synchronisatie stopt.', ['koppeling' => $def->label]), $days <= 3 ? 'danger' : 'warning');

        foreach (Mails::getAdminNotificationEmails() as $email) {
            rescue(fn () => \Illuminate\Support\Facades\Mail::to($email)->send(new \Dashed\DashedCore\Mail\NotificationMail($title, $title)), report: false);
        }
    }

    protected function notifyAdmins(string $title, ?string $body, string $status): void
    {
        foreach (\Dashed\DashedCore\Models\User::query()->whereIn('role', ['admin', 'superadmin'])->get() as $admin) {
            rescue(fn () => \Filament\Notifications\Notification::make()
                ->title($title)
                ->body($body)
                ->status($status)
                ->sendToDatabase($admin), report: false);
        }
    }
}

<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\Redirect;

class CleanupExpiredRedirects extends Command
{
    protected $signature = 'dashed:cleanup-expired-redirects {--dry-run : Alleen tonen hoeveel redirects verwijderd zouden worden, zonder te verwijderen}';

    protected $description = 'Verwijder redirects waarvan de "delete redirect after"-datum verstreken is, zodat ze uit het overzicht verdwijnen en niet meer redirecten.';

    /**
     * De Redirect (soft delete) wordt zowel in het admin-overzicht als in de
     * frontend-redirect (FrontendController) via de default scope opgevraagd,
     * dus een soft delete haalt de redirect uit de lijst en stopt het
     * redirecten - precies wat "delete redirect after <datum>" impliceert.
     */
    public function handle(): int
    {
        $expired = Redirect::whereNotNull('delete_redirect_after')
            ->whereDate('delete_redirect_after', '<', today());

        if ($this->option('dry-run')) {
            $this->info("{$expired->count()} expired redirect(s) would be pruned.");

            return self::SUCCESS;
        }

        $count = $expired->delete();

        $this->info("Deleted {$count} expired redirect(s).");

        return self::SUCCESS;
    }
}

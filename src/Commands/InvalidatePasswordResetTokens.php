<?php

namespace Dashed\DashedCore\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Dashed\DashedCore\Models\User;

class InvalidatePasswordResetTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:invalidatepasswordresettokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate all password reset tokens';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        User::where('password_reset_requested', '<', Carbon::now()->subHour())->update([
            'password_reset_token' => null,
            'password_reset_requested' => null,
        ]);
    }
}

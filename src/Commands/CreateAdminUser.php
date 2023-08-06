<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a admin user for the Qcommerce admin';

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
        $this->info('We are going to create a admin user for the Qcommerce CMS!');
        $firstName = $this->ask('What is your first name?');
        $lastName = $this->ask('What is your last name?');
        $email = $this->ask('What is your email?');
        $password = $this->secret('What is your password?');

        $user = new User();
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->email = $email;
        $user->role = 'admin';
        $user->password = Hash::make($password);
        $user->save();

        $this->info("The user with email $email has been created!");
    }
}

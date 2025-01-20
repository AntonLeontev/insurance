<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Email');
        $name = $this->ask('Name');
        $password = $this->ask('Password');

        $user = User::create([
            'email' => $email,
            'name' => $name,
            'password' => Hash::make($password),
        ]);

        if ($user) {
            $this->info("User {$user->name} created");
        }
    }
}

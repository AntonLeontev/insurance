<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DBRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:refresh';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('migrate:fresh');

        $agency = Agency::create([
            'name' => 'ООО Рога и Ко',
        ]);

        User::create([
            'email' => 'aner-anton@yandex.ru',
            'password' => Hash::make('Aner0102+-'),
            'name' => 'Anton',
            'agency_id' => $agency->id,
        ]);
    }
}

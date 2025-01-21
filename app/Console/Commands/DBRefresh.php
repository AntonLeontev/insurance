<?php

namespace App\Console\Commands;

use App\Enums\Ffd;
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
            'name' => 'АТОЛ',
            'inn' => '5544332219',
            'email' => '',
            'sno' => '',
            'payment_address' => 'https://v4.online.atol.ru',
            'group_code' => 'v4-online-atol-ru_4179',
            'ffd' => Ffd::FFD1_05,
            'atol_login' => 'v4-online-atol-ru',
            'atol_password' => 'iGFFuihss',
        ]);

        User::create([
            'email' => 'aner-anton@yandex.ru',
            'password' => Hash::make('Aner0102+-'),
            'name' => 'Anton',
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
    }
}

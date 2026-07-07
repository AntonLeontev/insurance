<?php

namespace App\Console\Commands;

use App\Enums\Ffd;
use App\Enums\Sno;
use App\Models\Agency;
use App\Models\FiscalCredential;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DBRefresh extends Command
{
    protected $signature = 'db:refresh';

    public function handle()
    {
        $this->call('migrate:fresh');

        $agency = Agency::create([
            'name' => 'АТОЛ',
            'inn' => '5544332219',
        ]);

        FiscalCredential::create([
            'agency_id' => $agency->id,
            'name' => 'АТОЛ',
            'is_default' => true,
            'inn' => '5544332219',
            'email' => 'test@example.com',
            'sno' => Sno::OSN,
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

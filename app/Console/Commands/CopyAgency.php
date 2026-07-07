<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CopyAgency extends Command
{
    protected $signature = 'app:copy-agency {id : ID исходного агентства}';

    protected $description = 'Создать копию агентства со всеми фискальными записями, страховщиками и контрактами (без чеков)';

    public function handle(): int
    {
        $source = Agency::with('insurers.contracts')->find($this->argument('id'));

        if ($source === null) {
            $this->error("Агентство с id {$this->argument('id')} не найдено");

            return self::FAILURE;
        }

        [$newAgency, $credentialsCount, $insurersCount, $contractsCount] = DB::transaction(function () use ($source) {
            $newAgency = $source->replicate();
            $newAgency->name = $source->name.' (Копия)';
            $newAgency->save();

            $credentialMap = [];
            $credentialsCount = 0;

            foreach ($source->fiscalCredentials as $credential) {
                $copy = $credential->replicate();
                $copy->agency_id = $newAgency->id;
                $copy->save();

                $credentialMap[$credential->id] = $copy->id;
                $credentialsCount++;
            }

            $insurersCount = 0;
            $contractsCount = 0;

            foreach ($source->insurers as $insurer) {
                $insurerCopy = $insurer->replicate();
                $insurerCopy->agency_id = $newAgency->id;
                $insurerCopy->fiscal_credential_id = $credentialMap[$insurer->fiscal_credential_id] ?? null;
                $insurerCopy->save();

                $insurersCount++;

                foreach ($insurer->contracts as $contract) {
                    $contractCopy = $contract->replicate();
                    $contractCopy->insurer_id = $insurerCopy->id;
                    $contractCopy->save();

                    $contractsCount++;
                }
            }

            $user = User::where('email', 'aner-anton@yandex.ru')->first();

            if ($user !== null) {
                $role = $source->users()
                    ->where('users.id', $user->id)
                    ->first()?->pivot->role ?? Role::ADMIN;

                $newAgency->users()->attach($user->id, ['role' => $role]);
            }

            return [$newAgency, $credentialsCount, $insurersCount, $contractsCount];
        });

        $this->info("Агентство скопировано: #{$newAgency->id} «{$newAgency->name}»");
        $this->info("Фискальных записей: {$credentialsCount}, страховщиков: {$insurersCount}, контрактов: {$contractsCount}");

        $user = User::where('email', 'aner-anton@yandex.ru')->first();

        if ($user === null) {
            $this->warn('Пользователь aner-anton@yandex.ru не найден — привязка пропущена');
        }

        return self::SUCCESS;
    }
}

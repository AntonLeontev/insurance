<?php

namespace App\Console\Commands;

use App\Services\Backup\UploadDatabaseSnapshot;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Дамп базы данных и выгрузка в Cloud.ru Object Storage';

    public function handle(UploadDatabaseSnapshot $upload): int
    {
        if ($upload->run() !== 0) {
            $this->error('Не удалось создать бэкап');

            return 1;
        }

        $this->info('Бэкап выгружен в Cloud.ru');

        return 0;
    }
}

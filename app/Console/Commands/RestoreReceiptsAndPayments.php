<?php

namespace App\Console\Commands;

use App\Services\Backup\RestoreReceiptsAndPayments as RestoreReceiptsAndPaymentsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestoreReceiptsAndPayments extends Command
{
    protected $signature = 'app:restore-from-backup {--dry-run : Проверки и сводка без записи}';

    protected $description = 'Восстановить чеки и недостающие платежи из базы бэкапа';

    public function handle(RestoreReceiptsAndPaymentsService $restore): int
    {
        if (! $this->backupIsReachable()) {
            return 1;
        }

        $exitCode = $restore->run((bool) $this->option('dry-run'));
        $summary = $restore->summary();
        $suffix = $this->option('dry-run') ? ' (dry-run)' : '';

        $this->info(sprintf(
            'Вставлено чеков: %d, обновлено сверок: %d, вставлено платежей: %d, пропущено: %d, конфликтов: %d, ошибок: %d%s',
            $summary['inserted_receipts'],
            $summary['updated_checks'],
            $summary['inserted_payments'],
            $summary['skipped'],
            $summary['conflicts'],
            $summary['errors'],
            $suffix,
        ));

        return $exitCode;
    }

    private function backupIsReachable(): bool
    {
        try {
            DB::connection('backup')->getPdo();
        } catch (Throwable $exception) {
            Log::error('[RestoreReceiptsAndPayments] Подключение backup недоступно', [
                'connection' => 'backup',
                'database' => config('database.connections.backup.database'),
                'exception' => $exception->getMessage(),
            ]);

            $this->error('Нет соединения с базой бэкапа');

            return false;
        }

        return true;
    }
}

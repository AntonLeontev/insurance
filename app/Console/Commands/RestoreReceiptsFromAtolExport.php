<?php

namespace App\Console\Commands;

use App\Services\Kkt\RestoreReceiptsFromAtolExport as RestoreReceiptsFromAtolExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestoreReceiptsFromAtolExport extends Command
{
    protected $signature = 'app:restore-receipts-from-kkt
        {file : Путь к xlsx-журналу кассы}
        {--agency=5 : Агентство}
        {--fallback-email=habsm@sistema-mart.ru : Резервная почта}
        {--dry-run : Сводка без записи}';

    protected $description = 'Восстановить отсутствующие чеки из xlsx-журнала кассы АТОЛ';

    public function handle(RestoreReceiptsFromAtolExportService $restore): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            Log::error('[RestoreReceiptsFromAtolExport] Путь не является файлом', [
                'file' => $file,
            ]);
            $this->error('Путь не является файлом');

            return 1;
        }

        $exitCode = $restore->run(
            $file,
            (int) $this->option('agency'),
            (string) $this->option('fallback-email'),
            (bool) $this->option('dry-run'),
        );
        $stopMessage = $restore->stopMessage();

        if ($stopMessage !== null) {
            $this->error($stopMessage);
        }

        $summary = $restore->summary();
        $suffix = $this->option('dry-run') ? ' (dry-run)' : '';

        $this->info(sprintf(
            'Всего строк: %d, вставлено: %d, сопоставлено по почте: %d, назначено резерву: %d, пропущено как дубль: %d, пропущено по статусу: %d, ошибок: %d%s',
            $summary['total'],
            $summary['inserted'],
            $summary['matched_by_email'],
            $summary['assigned_to_fallback'],
            $summary['skipped_duplicate'],
            $summary['skipped_status'],
            $summary['errors'],
            $suffix,
        ));

        return $exitCode;
    }
}

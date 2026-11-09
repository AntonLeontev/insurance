<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\DbSnapshots\Events\CreatedSnapshot;
use Spatie\DbSnapshots\Snapshot;
use Throwable;

class UploadDatabaseSnapshot
{
    public function __construct(
        protected BackupRetention $retention,
    ) {}

    public function run(): int
    {
        $snapshot = $this->createSnapshot();

        if (! $snapshot instanceof Snapshot) {
            return 1;
        }

        $uploaded = $this->upload($snapshot);

        if (! $uploaded) {
            return 1;
        }

        if (! $this->pruneRemote($snapshot->fileName)) {
            return 1;
        }

        return $this->deleteLocal($snapshot->fileName) ? 0 : 1;
    }

    private function createSnapshot(): ?Snapshot
    {
        $snapshot = null;

        Event::listen(CreatedSnapshot::class, function (CreatedSnapshot $event) use (&$snapshot): void {
            $snapshot = $event->snapshot;
        });

        try {
            $exitCode = Artisan::call('snapshot:create', [
                '--compress' => true,
            ]);
        } catch (Throwable $exception) {
            Log::error('Не удалось создать снимок базы', [
                'filename' => null,
                'disk' => 'snapshots',
                'exception' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            Event::forget(CreatedSnapshot::class);
        }

        if ($exitCode !== 0 || ! $snapshot instanceof Snapshot) {
            Log::error('Не удалось создать снимок базы', [
                'filename' => $snapshot instanceof Snapshot ? $snapshot->fileName : null,
                'disk' => 'snapshots',
                'exception' => "snapshot:create завершилась с кодом {$exitCode}",
            ]);

            return null;
        }

        return $snapshot;
    }

    private function upload(Snapshot $snapshot): bool
    {
        $stream = Storage::disk('snapshots')->readStream($snapshot->fileName);

        if (! is_resource($stream)) {
            Log::error('Не удалось прочитать локальный снимок', [
                'filename' => $snapshot->fileName,
                'disk' => 'snapshots',
                'exception' => 'Локальный поток снимка недоступен',
            ]);

            return false;
        }

        try {
            $stored = Storage::disk('backups')->writeStream($snapshot->fileName, $stream);
        } catch (Throwable $exception) {
            Log::error('Не удалось загрузить снимок в Cloud.ru', [
                'filename' => $snapshot->fileName,
                'disk' => 'backups',
                'exception' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($stored === false) {
            Log::error('Не удалось загрузить снимок в Cloud.ru', [
                'filename' => $snapshot->fileName,
                'disk' => 'backups',
                'exception' => 'Запись потока в объектное хранилище не выполнена',
            ]);

            return false;
        }

        return true;
    }

    private function pruneRemote(string $uploadedFilename): bool
    {
        try {
            $decision = $this->retention->partition(Storage::disk('backups')->files(), now());

            foreach ($decision['unrecognized'] as $filename) {
                Log::warning('Имя бэкапа не распознано, файл пропущен', [
                    'filename' => $filename,
                    'disk' => 'backups',
                ]);
            }

            foreach ($decision['delete'] as $filename) {
                Storage::disk('backups')->delete($filename);
            }
        } catch (Throwable $exception) {
            Log::error('Не удалось удалить устаревшие бэкапы', [
                'filename' => $uploadedFilename,
                'disk' => 'backups',
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function deleteLocal(string $filename): bool
    {
        try {
            $deleted = Storage::disk('snapshots')->delete($filename);
        } catch (Throwable $exception) {
            Log::error('Не удалось удалить локальный снимок', [
                'filename' => $filename,
                'disk' => 'snapshots',
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        if ($deleted === false) {
            Log::error('Не удалось удалить локальный снимок', [
                'filename' => $filename,
                'disk' => 'snapshots',
                'exception' => 'Локальный файл снимка остался на диске',
            ]);

            return false;
        }

        return true;
    }
}

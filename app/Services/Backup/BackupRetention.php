<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

class BackupRetention
{
    /**
     * @param  list<string>  $filenames
     * @return array{delete: list<string>, unrecognized: list<string>}
     */
    public function partition(array $filenames, CarbonInterface $now): array
    {
        $timezone = config('app.timezone');
        $moment = CarbonImmutable::parse($now)->timezone($timezone);
        $keepAfter = $moment->subHours(24);
        $oldestDay = $moment->startOfDay()->subDays(29);

        $parsed = [];
        $unrecognized = [];

        foreach ($filenames as $filename) {
            $createdAt = $this->createdAt($filename, $timezone);

            if ($createdAt === null) {
                $unrecognized[] = $filename;

                continue;
            }

            $parsed[] = [
                'filename' => $filename,
                'createdAt' => $createdAt,
            ];
        }

        $latestByDay = [];

        foreach ($parsed as $file) {
            $day = $file['createdAt']->toDateString();

            if ($file['createdAt']->startOfDay()->lessThan($oldestDay)) {
                continue;
            }

            if (! isset($latestByDay[$day]) || $file['createdAt']->greaterThan($latestByDay[$day])) {
                $latestByDay[$day] = $file['createdAt'];
            }
        }

        $delete = [];

        foreach ($parsed as $file) {
            if ($file['createdAt']->greaterThanOrEqualTo($keepAfter)) {
                continue;
            }

            $day = $file['createdAt']->toDateString();

            if (isset($latestByDay[$day]) && $file['createdAt']->equalTo($latestByDay[$day])) {
                continue;
            }

            $delete[] = $file['filename'];
        }

        return [
            'delete' => $delete,
            'unrecognized' => $unrecognized,
        ];
    }

    private function createdAt(string $filename, string $timezone): ?CarbonImmutable
    {
        $basename = pathinfo($filename, PATHINFO_BASENAME);

        if (! preg_match('/^(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql(?:\.gz)?$/', $basename, $matches)) {
            return null;
        }

        try {
            $createdAt = CarbonImmutable::createFromFormat('!Y-m-d_H-i-s', $matches[1], $timezone);
        } catch (Throwable) {
            return null;
        }

        if (! $createdAt instanceof CarbonImmutable || $createdAt->format('Y-m-d_H-i-s') !== $matches[1]) {
            return null;
        }

        return $createdAt;
    }
}

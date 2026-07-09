<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup implements ShouldQueue
{
    use Queueable;

    // pg_dump bisa memakan waktu; jangan auto-retry — kalau gagal lebih aman
    // terlihat di log & diselidiki manual daripada berjalan dobel menimpa file
    // tmp yang sama.
    public int $timeout = 600;

    public int $tries = 1;

    public function handle(): void
    {
        $db = config('database.connections.pgsql.database');
        $user = config('database.connections.pgsql.username');
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port', 5432);
        $filename = 'walisantri_'.now()->format('Y-m-d').'.dump.gz';
        $tmpPath = sys_get_temp_dir().'/'.$filename;

        // pg_dump -Fc (custom format) → gzip (§6.3)
        $cmd = sprintf(
            'PGPASSWORD=%s pg_dump -Fc -h %s -p %s -U %s %s | gzip > %s 2>&1',
            escapeshellarg(config('database.connections.pgsql.password')),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($db),
            escapeshellarg($tmpPath),
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::error('DatabaseBackup: pg_dump gagal', ['output' => $output]);
            $this->fail('pg_dump exit code: '.$exitCode);

            return;
        }

        // Upload ke R2 walisantri-backup/daily/ (§6.2)
        $r2Path = 'daily/'.now()->format('Y/m/').$filename;
        Storage::disk('r2-backup')->put($r2Path, fopen($tmpPath, 'r'));

        unlink($tmpPath);

        Log::info('DatabaseBackup: sukses', ['path' => $r2Path, 'size' => Storage::disk('r2-backup')->size($r2Path)]);
    }
}

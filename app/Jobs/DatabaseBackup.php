<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DatabaseBackup implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // TODO: implementasi §11
    }
}

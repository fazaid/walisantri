<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneStaleCache implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // TODO: implementasi §11
    }
}

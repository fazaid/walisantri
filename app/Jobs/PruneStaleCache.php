<?php

namespace App\Jobs;

use App\Models\Santri;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class PruneStaleCache implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Hapus cache dashboard santri yang sudah non-aktif (§11)
        Santri::allTenants()
            ->where('status_aktif', false)
            ->select(['uuid'])
            ->eachById(function (Santri $santri) {
                Cache::forget("dashboard_wali:{$santri->uuid}");
            });
    }
}

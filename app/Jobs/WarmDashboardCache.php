<?php

namespace App\Jobs;

use App\Models\Santri;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class WarmDashboardCache implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Pre-generate cache dashboard untuk santri aktif (§4.5)
        // Cache key: "dashboard_wali:{santri_uuid}", TTL 30 menit
        Santri::allTenants()
            ->where('status_aktif', true)
            ->with(['wali', 'pesantren'])
            ->select(['id', 'uuid', 'pesantren_id', 'wali_santri_id', 'nama_lengkap', 'kelas', 'kamar'])
            ->eachById(function (Santri $santri) {
                Cache::put(
                    "dashboard_wali:{$santri->uuid}",
                    $santri->toArray(),
                    now()->addMinutes(30),
                );
            });
    }
}

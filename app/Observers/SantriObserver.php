<?php

namespace App\Observers;

use App\Models\Santri;

class SantriObserver
{
    public function created(Santri $santri): void
    {
        ActivityLogger::log('santri.created', $santri, null, [
            'nis'           => $santri->nis,
            'nama_lengkap'  => $santri->nama_lengkap,
        ]);
    }

    public function deleted(Santri $santri): void
    {
        ActivityLogger::log('santri.deleted', $santri, [
            'nis'          => $santri->nis,
            'nama_lengkap' => $santri->nama_lengkap,
        ]);
    }
}

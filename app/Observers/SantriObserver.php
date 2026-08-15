<?php

namespace App\Observers;

use App\Enums\OnboardingStep;
use App\Exceptions\SantriQuotaExceededException;
use App\Models\Santri;
use App\Observers\Concerns\ReplacesUploadedFile;
use App\Support\KodePresensi;

class SantriObserver
{
    use ReplacesUploadedFile;

    public function creating(Santri $santri): void
    {
        // SEBELUM kedua early-return di bawah, dan itu disengaja: santri non-aktif
        // pun harus punya kode kartu, kalau tidak ia lahir tanpa kode dan tidak
        // akan pernah bisa dicetak kartunya saat suatu hari diaktifkan kembali.
        if (blank($santri->kode_presensi)) {
            $santri->kode_presensi = KodePresensi::buat();
        }

        if ($santri->status_aktif === false) {
            return;
        }

        $pesantren = $santri->pesantren;

        if (! $pesantren) {
            return;
        }

        if ($pesantren->isQuotaFull()) {
            throw new SantriQuotaExceededException($pesantren);
        }
    }

    public function created(Santri $santri): void
    {
        ActivityLogger::log('santri.created', $santri, null, [
            'nis' => $santri->nis,
            'nama_lengkap' => $santri->nama_lengkap,
        ]);

        $santri->pesantren?->completeOnboardingStep(OnboardingStep::Santri);
    }

    public function updating(Santri $santri): void
    {
        $this->deleteOldFileIfReplaced($santri, 'foto_profil');
    }

    public function deleted(Santri $santri): void
    {
        $this->deleteFile($santri, 'foto_profil');

        ActivityLogger::log('santri.deleted', $santri, [
            'nis' => $santri->nis,
            'nama_lengkap' => $santri->nama_lengkap,
        ]);
    }

    public function forceDeleted(Santri $santri): void
    {
        $this->deleteFile($santri, 'foto_profil');
    }
}

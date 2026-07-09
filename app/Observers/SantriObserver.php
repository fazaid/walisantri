<?php

namespace App\Observers;

use App\Enums\OnboardingStep;
use App\Exceptions\SantriQuotaExceededException;
use App\Models\Santri;
use Illuminate\Support\Facades\Storage;

class SantriObserver
{
    public function creating(Santri $santri): void
    {
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
            'nis'           => $santri->nis,
            'nama_lengkap'  => $santri->nama_lengkap,
        ]);

        $santri->pesantren?->completeOnboardingStep(OnboardingStep::Santri);
    }

    public function updating(Santri $santri): void
    {
        if ($santri->isDirty('foto_profil') && $santri->getOriginal('foto_profil')) {
            Storage::disk('public')->delete($santri->getOriginal('foto_profil'));
        }
    }

    public function deleted(Santri $santri): void
    {
        if ($santri->foto_profil) {
            Storage::disk('public')->delete($santri->foto_profil);
        }

        ActivityLogger::log('santri.deleted', $santri, [
            'nis'          => $santri->nis,
            'nama_lengkap' => $santri->nama_lengkap,
        ]);
    }

    public function forceDeleted(Santri $santri): void
    {
        if ($santri->foto_profil) {
            Storage::disk('public')->delete($santri->foto_profil);
        }
    }
}

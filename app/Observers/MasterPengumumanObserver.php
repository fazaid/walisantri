<?php

namespace App\Observers;

use App\Enums\OnboardingStep;
use App\Models\MasterPengumuman;

class MasterPengumumanObserver
{
    public function created(MasterPengumuman $pengumuman): void
    {
        if (! $pengumuman->pesantren_id) {
            return; // pengumuman global super_admin, bukan milik tenant manapun
        }

        $pengumuman->pesantren?->completeOnboardingStep(OnboardingStep::Pengumuman);
    }
}

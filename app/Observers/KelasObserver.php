<?php

namespace App\Observers;

use App\Enums\OnboardingStep;
use App\Models\Kelas;

class KelasObserver
{
    public function created(Kelas $kelas): void
    {
        $kelas->pesantren?->completeOnboardingStep(OnboardingStep::Kelas);
    }
}

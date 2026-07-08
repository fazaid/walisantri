<?php

namespace App\Observers;

use App\Enums\OnboardingStep;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->role === UserRole::Ustadz->value) {
            $user->pesantren?->completeOnboardingStep(OnboardingStep::Ustadz);
        }
    }

    public function updating(User $user): void
    {
        if ($user->isDirty('foto_profil') && $user->getOriginal('foto_profil')) {
            Storage::disk('public')->delete($user->getOriginal('foto_profil'));
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('role')) {
            ActivityLogger::log('user.role_changed', $user,
                ['role' => $user->getOriginal('role')],
                ['role' => $user->role],
            );
        }

        if ($user->wasChanged('password')) {
            ActivityLogger::log('user.password_reset', $user);
        }
    }
}

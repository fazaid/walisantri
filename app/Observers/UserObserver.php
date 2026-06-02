<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
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

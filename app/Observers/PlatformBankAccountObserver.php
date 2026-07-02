<?php

namespace App\Observers;

use App\Models\PlatformBankAccount;
use Illuminate\Support\Facades\Storage;

class PlatformBankAccountObserver
{
    public function updating(PlatformBankAccount $bankAccount): void
    {
        if ($bankAccount->isDirty('logo') && $bankAccount->getOriginal('logo')) {
            Storage::disk('public')->delete($bankAccount->getOriginal('logo'));
        }
    }

    public function deleted(PlatformBankAccount $bankAccount): void
    {
        if ($bankAccount->logo) {
            Storage::disk('public')->delete($bankAccount->logo);
        }
    }
}

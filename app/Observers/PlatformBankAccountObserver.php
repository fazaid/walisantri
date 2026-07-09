<?php

namespace App\Observers;

use App\Models\PlatformBankAccount;
use App\Observers\Concerns\ReplacesUploadedFile;

class PlatformBankAccountObserver
{
    use ReplacesUploadedFile;

    public function updating(PlatformBankAccount $bankAccount): void
    {
        $this->deleteOldFileIfReplaced($bankAccount, 'logo');
    }

    public function deleted(PlatformBankAccount $bankAccount): void
    {
        $this->deleteFile($bankAccount, 'logo');
    }
}

<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Santri;
use App\Observers\ActivityLogger;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegenerasiUuidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'regenerasi_uuid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Regenerasi Link')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Regenerasi Link Portal Wali?')
            ->modalDescription('Link lama yang sudah dikirim ke wali akan TIDAK VALID setelah ini. Wali harus menggunakan link baru.')
            ->modalSubmitActionLabel('Ya, Regenerasi')
            ->visible(fn () => Auth::user()?->role === 'admin_pesantren')
            ->action(function (Santri $record) {
                $oldUuid = $record->uuid;

                $record->uuid = (string) Str::uuid();
                $record->saveQuietly();

                ActivityLogger::log('santri.uuid_regenerated', $record, [
                    'uuid' => $oldUuid,
                ], [
                    'uuid' => $record->uuid,
                ]);

                Notification::make()
                    ->title('Link berhasil di-regenerasi')
                    ->body('Link lama sudah tidak valid. Kirimkan link baru ke wali santri.')
                    ->warning()
                    ->send();
            });
    }
}

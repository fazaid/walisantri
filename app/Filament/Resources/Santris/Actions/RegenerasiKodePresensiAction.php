<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Santri;
use App\Observers\ActivityLogger;
use App\Support\KodePresensi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Ganti kode kartu presensi santri — untuk kartu yang hilang atau terlanjur
 * difoto orang lain. Pola sama persis dengan RegenerasiUuidAction.
 */
class RegenerasiKodePresensiAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'regenerasi_kode_presensi';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Ganti Kode Kartu')
            ->icon('heroicon-o-qr-code')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Ganti Kode Kartu Presensi?')
            ->modalDescription('Kartu lama akan langsung tidak berlaku dan harus dicetak ulang. Riwayat presensi yang sudah tercatat tidak terpengaruh.')
            ->modalSubmitActionLabel('Ya, Ganti Kode')
            ->visible(fn () => Auth::user()?->role === 'admin_pesantren')
            ->action(function (Santri $record) {
                $lama = $record->kode_presensi;

                $record->kode_presensi = KodePresensi::buat();
                $record->kode_presensi_diperbarui_at = now();
                $record->saveQuietly();

                ActivityLogger::log('presensi.kode_diregenerasi', $record,
                    ['kode_presensi' => $lama],
                    ['kode_presensi' => $record->kode_presensi],
                );

                Notification::make()
                    ->title('Kode kartu diganti')
                    ->body('Kartu lama sudah tidak berlaku. Cetak ulang kartu santri ini.')
                    ->warning()
                    ->send();
            });
    }
}

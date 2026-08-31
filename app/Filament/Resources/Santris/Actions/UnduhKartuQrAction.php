<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Santri;
use App\Services\KartuPresensiPdf;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

/**
 * Unduh kartu QR satu santri dari halaman detailnya.
 *
 * Sebelum ini satu-satunya cara mencetak kartu adalah mencetak seluruh kelas —
 * mahal untuk kasus yang paling sering terjadi, yaitu satu anak kehilangan
 * kartunya dan kodenya baru saja diganti lewat Ganti Kode Kartu Presensi.
 */
class UnduhKartuQrAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unduh_kartu_qr';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Unduh Kartu QR')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            // Sejajar KirimMagicLinkAction: ustadz boleh mencetak ulang kartu
            // santri bimbingannya, yang tidak boleh dia lakukan adalah mengganti
            // kodenya (lihat RegenerasiKodePresensiAction).
            ->visible(fn (): bool => in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']))
            ->disabled(fn (Santri $record): bool => $record->kode_presensi === null)
            ->tooltip(fn (Santri $record): ?string => $record->kode_presensi === null
                ? 'Santri ini belum punya kode kartu. Buat dulu lewat aksi Ganti Kode Kartu Presensi.'
                : null)
            // Response yang dikembalikan action langsung jadi unduhan (Livewire
            // SupportFileDownloads) — tidak perlu rute sendiri.
            ->action(fn (Santri $record) => app(KartuPresensiPdf::class)->untukSantri($record));
    }
}

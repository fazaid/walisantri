<?php

namespace App\Services;

use App\Enums\StatusPengajuanIzin;
use App\Enums\SumberPresensi;
use App\Models\KesantrianMutabaah;
use App\Models\Presensi;
use App\Models\PresensiIzin;
use App\Models\User;
use App\Observers\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pemegang seluruh transisi status pengajuan izin beserta efek sampingnya.
 *
 * Logikanya di service, bukan di dalam aksi Filament — pola `UpgradeOrderService`.
 * Alasannya konkret: izin bisa disetujui dari panel admin DAN (nanti) dari jalur
 * lain; menaruh penulisan baris presensi di dalam aksi berarti jalur kedua harus
 * menyalinnya, dan salinan itulah yang akan menyimpang.
 */
class PresensiIzinService
{
    /**
     * Setujui izin: tulis baris presensi untuk tiap tanggal dalam rentang.
     *
     * Hari libur DILEWATI — mencatat "Sakit" pada hari yang memang tidak ada
     * kegiatan akan mengotori penyebut rekap dan membuat santri tampak absen di
     * hari yang tidak pernah menuntut kehadirannya.
     */
    public function setujui(PresensiIzin $izin, User $petugas, ?string $catatan = null): PresensiIzin
    {
        $kalender = PresensiKalender::untuk($izin->pesantren_id);
        $status = $izin->jenis->keStatusKehadiran();
        $sekarang = now();

        DB::transaction(function () use ($izin, $petugas, $catatan, $kalender, $status, $sekarang): void {
            $izin->update([
                'status' => StatusPengajuanIzin::Disetujui,
                'diproses_oleh' => $petugas->id,
                'diproses_at' => $sekarang,
                'catatan_petugas' => $catatan,
            ]);

            $baris = [];

            foreach ($this->tanggalDalamRentang($izin) as $tanggal) {
                if ($kalender->adalahLibur($tanggal)) {
                    continue;
                }

                $baris[] = [
                    'pesantren_id' => $izin->pesantren_id,
                    'santri_id' => $izin->santri_id,
                    'tanggal' => $tanggal,
                    'jam_ke' => Presensi::HARIAN,
                    'kelas_id' => $izin->santri?->kelas_id,
                    'status' => $status->value,
                    'catatan' => 'Izin: '.$izin->jenis->label(),
                    'sumber' => SumberPresensi::Izin->value,
                    'presensi_izin_id' => $izin->id,
                    'dicatat_oleh' => $petugas->id,
                    'dicatat_at' => $sekarang,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }

            if ($baris !== []) {
                // upsert, bukan insert: hari yang sudah terlanjur diabsen manual
                // ditimpa oleh keputusan izin yang baru disetujui — dan itu memang
                // yang diharapkan, karena persetujuan adalah keputusan yang lebih
                // baru. Sekaligus bebas balapan (pelajaran v4.27).
                Presensi::upsert(
                    $baris,
                    ['santri_id', 'tanggal', 'jam_ke'],
                    ['status', 'catatan', 'sumber', 'presensi_izin_id', 'kelas_id', 'dicatat_oleh', 'dicatat_at', 'updated_at'],
                );
            }

            $this->selaraskanUdzurMutabaah($izin);
        });

        ActivityLogger::log('presensi.izin_disetujui', $izin, null, [
            'santri_id' => $izin->santri_id,
            'jenis' => $izin->jenis->value,
            'tanggal_mulai' => $izin->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $izin->tanggal_selesai->toDateString(),
        ]);

        return $izin->refresh();
    }

    public function tolak(PresensiIzin $izin, User $petugas, ?string $catatan = null): PresensiIzin
    {
        $izin->update([
            'status' => StatusPengajuanIzin::Ditolak,
            'diproses_oleh' => $petugas->id,
            'diproses_at' => now(),
            'catatan_petugas' => $catatan,
        ]);

        ActivityLogger::log('presensi.izin_ditolak', $izin, null, [
            'santri_id' => $izin->santri_id,
            'catatan' => $catatan,
        ]);

        return $izin->refresh();
    }

    /**
     * Batalkan izin yang sudah disetujui, dan bersihkan jejaknya di presensi.
     *
     * ⚠️ Hanya baris yang MASIH bersumber 'izin' yang dihapus. Baris yang sejak itu
     * disunting ustadz sudah berpindah ke sumber 'manual', dan koreksi manusia tidak
     * boleh dihapus oleh pembatalan otomatis — orang yang membatalkan izin tidak
     * sedang menyatakan bahwa catatan manual itu salah.
     */
    public function batalkan(PresensiIzin $izin, User $petugas, ?string $catatan = null): PresensiIzin
    {
        DB::transaction(function () use ($izin, $petugas, $catatan): void {
            Presensi::where('presensi_izin_id', $izin->id)
                ->where('sumber', SumberPresensi::Izin->value)
                ->delete();

            $izin->update([
                'status' => StatusPengajuanIzin::Dibatalkan,
                'diproses_oleh' => $petugas->id,
                'diproses_at' => now(),
                'catatan_petugas' => $catatan,
            ]);
        });

        ActivityLogger::log('presensi.izin_dibatalkan', $izin, null, [
            'santri_id' => $izin->santri_id,
            'catatan' => $catatan,
        ]);

        return $izin->refresh();
    }

    /**
     * Selaraskan status_udzur mutaba'ah untuk tanggal-tanggal izin.
     *
     * ⚠️ HANYA memperbarui baris yang SUDAH ADA — tidak pernah membuat baris baru.
     * Alasannya aritmetik, bukan gaya: MutabaahScoreCalculator::persentaseRataRata()
     * memasukkan setiap baris ke penyebut tanpa memandang udzur, jadi baris kosong
     * untuk hari izin akan menurunkan persentase amalan santri justru karena ia
     * berhalangan — dan angka itu dibaca wali di portal serta tercetak di rapor.
     *
     * Baris yang udzurnya sudah diisi manusia dengan keterangan lain (mis. Haid)
     * juga tidak ditimpa: itu penilaian yang lebih spesifik.
     */
    private function selaraskanUdzurMutabaah(PresensiIzin $izin): void
    {
        KesantrianMutabaah::where('santri_id', $izin->santri_id)
            ->whereBetween('tanggal', [
                $izin->tanggal_mulai->toDateString(),
                $izin->tanggal_selesai->toDateString(),
            ])
            ->where('status_udzur', 'Tidak')
            ->update(['status_udzur' => $izin->jenis->keStatusUdzur()]);
    }

    /** @return list<string> tanggal Y-m-d, inklusif kedua ujungnya */
    private function tanggalDalamRentang(PresensiIzin $izin): array
    {
        $mulai = Carbon::parse($izin->tanggal_mulai)->startOfDay();
        $selesai = Carbon::parse($izin->tanggal_selesai)->startOfDay();

        $tanggal = [];

        for ($t = $mulai->copy(); $t->lte($selesai); $t->addDay()) {
            $tanggal[] = $t->toDateString();
        }

        return $tanggal;
    }
}

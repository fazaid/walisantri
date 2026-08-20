<?php

namespace App\Filament\Widgets;

use App\Enums\Modul;
use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Santri;
use App\Services\PresensiKalender;
use App\Support\PenugasanUstadz;
use App\Support\Waktu;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Kehadiran hari ini — satu widget untuk admin pesantren dan ustadz sekaligus.
 *
 * Dibuat satu kelas, bukan sepasang widget Admin dan Ustadz terpisah seperti
 * tetangganya di direktori ini: yang berbeda antara keduanya hanya CAKUPAN kelasnya, sementara
 * ketiga angkanya identik. Menyalinnya jadi dua kelas berarti dua tempat yang
 * harus ikut berubah setiap kali definisi "belum diabsen" disesuaikan.
 *
 * ⚠️ SENGAJA TANPA CACHE, berbeda dari AdminStatsOverview yang menyimpan agregat
 * mingguan 15 menit. Angka "hari ini" yang basi lebih buruk daripada tidak ada:
 * ustadz yang baru saja mengisi presensi lalu melihat "belum diabsen" akan
 * mengisinya dua kali. Lingkupnya pun murah — satu hari, satu pesantren, dan
 * index (pesantren_id, tanggal, jam_ke) memang dibuat untuk query ini.
 */
class PresensiHariIniStat extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ], true)
            && Modul::Presensi->aktif();
    }

    protected function getStats(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;
        $hariIni = Waktu::hariIni();
        $kalender = PresensiKalender::untuk($pesantrenId);

        // Di hari libur, "belum diabsen" bukan kelalaian — jadi widgetnya
        // menjelaskan keadaan alih-alih menuduh.
        if ($kalender->adalahLibur($hariIni)) {
            return [
                Stat::make('Hari Ini Libur', $kalender->keteranganLibur($hariIni))
                    ->description('Presensi tidak diharapkan hari ini')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('gray'),
            ];
        }

        $kelasIds = $this->kelasDalamCakupan();
        $santriIds = $this->santriDalamCakupan($kelasIds);

        $presensiHariIni = Presensi::where('pesantren_id', $pesantrenId)
            ->where('jam_ke', Presensi::HARIAN)
            ->whereDate('tanggal', $hariIni)
            ->whereIn('santri_id', $santriIds)
            ->get(['santri_id', 'status', 'kelas_id']);

        $hadir = $presensiHariIni->filter(
            fn (Presensi $p) => $p->status->hadirEfektif()
        )->count();

        $tidakHadir = $presensiHariIni->count() - $hadir;

        $kelasSudahDiabsen = $presensiHariIni->pluck('kelas_id')->filter()->unique();
        $kelasBelumDiabsen = $kelasIds->diff($kelasSudahDiabsen)->count();

        return [
            Stat::make('Hadir Hari Ini', $hadir)
                ->description('Dari '.$santriIds->count().' santri')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Tidak Hadir Hari Ini', $tidakHadir)
                ->description($this->rincianTidakHadir($presensiHariIni))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($tidakHadir === 0 ? 'success' : ($tidakHadir <= 3 ? 'warning' : 'danger')),

            // Angka ketiga inilah alat manajemen sebenarnya: ia menunjukkan
            // disiplin PENCATATAN, bukan disiplin santri.
            Stat::make('Kelas Belum Diabsen', $kelasBelumDiabsen)
                ->description('Dari '.$kelasIds->count().' kelas')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($kelasBelumDiabsen === 0 ? 'success' : ($kelasBelumDiabsen <= 2 ? 'warning' : 'danger')),
        ];
    }

    /** @return Collection<int, int> */
    private function kelasDalamCakupan(): Collection
    {
        if (Auth::user()?->role === UserRole::Ustadz->value) {
            return PenugasanUstadz::kelasIdsPerwalian();
        }

        return Kelas::where('pesantren_id', Auth::user()?->pesantren_id)->pluck('id');
    }

    /** @param  Collection<int, int>  $kelasIds */
    private function santriDalamCakupan(Collection $kelasIds): Collection
    {
        $query = Santri::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('status_aktif', true);

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->whereIn('kelas_id', $kelasIds);
        }

        return $query->pluck('id');
    }

    /** @param  Collection<int, Presensi>  $presensi */
    private function rincianTidakHadir(Collection $presensi): string
    {
        $rincian = $presensi
            ->reject(fn (Presensi $p) => $p->status->hadirEfektif())
            ->countBy(fn (Presensi $p) => $p->status->value)
            ->map(fn (int $jumlah, string $status) => "{$jumlah} {$status}")
            ->implode(' · ');

        return $rincian !== '' ? $rincian : 'Semua hadir';
    }
}

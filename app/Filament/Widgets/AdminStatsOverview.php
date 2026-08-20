<?php

namespace App\Filament\Widgets;

use App\Enums\Modul;
use App\Filament\Pages\BillingPage;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Models\User;
use App\Services\MutabaahScoreCalculator;
use App\Support\Waktu;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminStatsOverview extends StatsOverviewWidget
{
    // Matikan polling default CanPoll (5s): dashboard agregat tak perlu refresh live,
    // dan request polling latar jadi sumber toast error saat wake-from-sleep.
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    protected function getStats(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;
        // Batas hari & pekan mengikuti kalender WIB; sisa hari langganan di
        // bawah tetap pakai now() karena itu selisih instan, bukan kalender.
        $today = Waktu::hariIni();
        $startOfWeek = Waktu::sekarang()->startOfWeek()->toDateString();
        $endOfWeek = Waktu::sekarang()->endOfWeek()->toDateString();

        // Data billing
        $pesantren = Auth::user()?->pesantren;
        $expiredAt = $pesantren?->expired_at
            ? Carbon::parse($pesantren->expired_at)
            : null;
        $sisaHari = $expiredAt
            ? (int) now()->diffInDays($expiredAt, false)
            : null;
        $statusLabel = match ($pesantren?->status_berlangganan) {
            'active' => 'Aktif',
            'trial' => 'Trial',
            'expired' => 'Kadaluwarsa',
            'suspended' => 'Ditangguhkan',
            default => '-',
        };
        $billingColor = match ($pesantren?->status_berlangganan) {
            'active' => 'success',
            'trial' => 'info',
            'expired' => 'danger',
            'suspended' => 'warning',
            default => 'gray',
        };

        // Total santri aktif vs kuota
        $totalSantri = Santri::where('pesantren_id', $pesantrenId)
            ->where('status_aktif', true)
            ->count();
        $kuota = $pesantren?->max_santri_kuota ?? 0;
        $persenKuota = $kuota > 0 ? round(($totalSantri / $kuota) * 100) : 0;

        // Total ustadz & wali
        $totalUstadz = User::where('pesantren_id', $pesantrenId)
            ->where('role', 'ustadz')
            ->count();
        $totalWali = User::where('pesantren_id', $pesantrenId)
            ->where('role', 'wali_santri')
            ->count();

        // Dua stat terakhir milik modul Kesantrian. Pemeriksaannya di sini, bukan
        // hanya saat merakit array di bawah: query kesehatan dan agregasi amalan
        // adalah bagian termahal widget ini, dan pesantren yang mematikan modulnya
        // tidak boleh tetap membayarnya di setiap load dashboard.
        $kesantrianAktif = Modul::Kesantrian->aktif();

        // Santri sakit hari ini
        $santriSakit = $kesantrianAktif
            ? KesantrianKesehatan::where('pesantren_id', $pesantrenId)
                ->where('tanggal_periksa', $today)
                ->whereIn('status_pemulihan', ['Istirahat_Total', 'Rujukan_Luar'])
                ->count()
            : 0;

        // Persentase amalan minggu ini (rata-rata seluruh santri) — di-cache 15
        // menit karena komputasinya (agregasi item amal dinamis per pesantren)
        // cukup berat untuk dihitung ulang di setiap load dashboard.
        $persenAmalan = ! $kesantrianAktif ? 0 : Cache::remember(
            "admin_stats:persen_amalan:{$pesantrenId}:{$startOfWeek}",
            now()->addMinutes(15),
            function () use ($pesantrenId, $startOfWeek, $endOfWeek) {
                $santriIds = Santri::where('pesantren_id', $pesantrenId)
                    ->where('status_aktif', true)
                    ->pluck('id');

                $mutabaahList = KesantrianMutabaah::whereIn('santri_id', $santriIds)
                    ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
                    ->get();

                return MutabaahScoreCalculator::persentaseRataRata($mutabaahList);
            }
        );

        $stats = [
            Stat::make('Santri Aktif', $totalSantri.' / '.$kuota)
                ->description($persenKuota.'% dari kuota paket')
                ->descriptionIcon('heroicon-m-users')
                ->color($persenKuota >= 90 ? 'danger' : 'success'),

            Stat::make('Ustadz Terdaftar', $totalUstadz)
                ->description('Pengajar aktif di pesantren')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Wali Santri', $totalWali)
                ->description('Akun wali terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Langganan', $statusLabel)
                ->description(
                    $sisaHari !== null
                        ? ($sisaHari > 0
                            ? 'Berakhir '.$expiredAt->translatedFormat('d M Y')
                            : ($sisaHari === 0 ? 'Berakhir hari ini' : 'Telah berakhir'))
                        : 'Paket: '.ucfirst($pesantren?->paket_langganan ?? '-')
                )
                ->descriptionIcon('heroicon-m-credit-card')
                ->url(BillingPage::getUrl())
                ->color($billingColor),
        ];

        if ($kesantrianAktif) {
            // Disisipkan sebelum stat Langganan supaya urutannya tidak berubah bagi
            // pesantren yang modulnya menyala — Langganan tetap yang paling kanan.
            array_splice($stats, 3, 0, [
                Stat::make('Santri Sakit Hari Ini', $santriSakit)
                    ->description('Istirahat total & rujukan luar')
                    ->descriptionIcon('heroicon-m-heart')
                    ->color($santriSakit > 0 ? 'danger' : 'success'),

                Stat::make('Amalan Minggu Ini', $persenAmalan.'%')
                    ->description('Rata-rata seluruh santri')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color($persenAmalan >= 75 ? 'success' : ($persenAmalan >= 50 ? 'warning' : 'danger')),
            ]);
        }

        return $stats;
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Models\User;
use App\Services\MutabaahScoreCalculator;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    protected function getStats(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;
        $today       = now()->toDateString();
        $startOfWeek = now()->startOfWeek()->toDateString();
        $endOfWeek   = now()->endOfWeek()->toDateString();

        // Data billing
        $pesantren    = Auth::user()?->pesantren;
        $expiredAt    = $pesantren?->expired_at
            ? \Carbon\Carbon::parse($pesantren->expired_at)
            : null;
        $sisaHari     = $expiredAt
            ? (int) now()->diffInDays($expiredAt, false)
            : null;
        $statusLabel  = match($pesantren?->status_berlangganan) {
            'active'    => 'Aktif',
            'trial'     => 'Trial',
            'expired'   => 'Kadaluwarsa',
            'suspended' => 'Ditangguhkan',
            default     => '-',
        };
        $billingColor = match($pesantren?->status_berlangganan) {
            'active'    => 'success',
            'trial'     => 'info',
            'expired'   => 'danger',
            'suspended' => 'warning',
            default     => 'gray',
        };

        // Total santri aktif vs kuota
        $totalSantri = Santri::where('pesantren_id', $pesantrenId)
            ->where('status_aktif', true)
            ->count();
        $kuota       = $pesantren?->max_santri_kuota ?? 0;
        $persenKuota = $kuota > 0 ? round(($totalSantri / $kuota) * 100) : 0;

        // Total ustadz & wali
        $totalUstadz = User::where('pesantren_id', $pesantrenId)
            ->where('role', 'ustadz')
            ->count();
        $totalWali   = User::where('pesantren_id', $pesantrenId)
            ->where('role', 'wali_santri')
            ->count();

        // Santri sakit hari ini
        $santriSakit = KesantrianKesehatan::where('pesantren_id', $pesantrenId)
            ->where('tanggal_periksa', $today)
            ->whereIn('status_pemulihan', ['Istirahat_Total', 'Rujukan_Luar'])
            ->count();

        // Persentase amalan minggu ini (rata-rata seluruh santri)
        $santriIds = Santri::where('pesantren_id', $pesantrenId)
            ->where('status_aktif', true)
            ->pluck('id');

        $mutabaahList  = KesantrianMutabaah::whereIn('santri_id', $santriIds)
            ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
            ->get();

        $persenAmalan = MutabaahScoreCalculator::persentaseRataRata($mutabaahList);

        return [
            Stat::make('Santri Aktif', $totalSantri . ' / ' . $kuota)
                ->description($persenKuota . '% dari kuota paket')
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

            Stat::make('Santri Sakit Hari Ini', $santriSakit)
                ->description('Istirahat total & rujukan luar')
                ->descriptionIcon('heroicon-m-heart')
                ->color($santriSakit > 0 ? 'danger' : 'success'),

            Stat::make('Amalan Minggu Ini', $persenAmalan . '%')
                ->description('Rata-rata seluruh santri')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($persenAmalan >= 75 ? 'success' : ($persenAmalan >= 50 ? 'warning' : 'danger')),

            Stat::make('Langganan', $statusLabel)
                ->description(
                    $sisaHari !== null
                        ? ($sisaHari > 0
                            ? 'Berakhir ' . $expiredAt->translatedFormat('d M Y')
                            : ($sisaHari === 0 ? 'Berakhir hari ini' : 'Telah berakhir'))
                        : 'Paket: ' . ucfirst($pesantren?->paket_langganan ?? '-')
                )
                ->descriptionIcon('heroicon-m-credit-card')
                ->url(route('filament.admin.pages.billing-page'))
                ->color($billingColor),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UstadzStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'ustadz';
    }

    protected function getStats(): array
    {
        $ustadzId    = Auth::id();
        $pesantrenId = Auth::user()?->pesantren_id;
        $today       = now()->toDateString();

        // Santri halaqah milik ustadz ini
        $santriHalaqah = Santri::where('pesantren_id', $pesantrenId)
            ->where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->pluck('id');

        $totalHalaqah = $santriHalaqah->count();

        // Setoran tahfidz hari ini dari santri halaqahnya
        $setoranHariIni = TahfidzProgress::where('pesantren_id', $pesantrenId)
            ->where('ustadz_id', $ustadzId)
            ->where('tanggal', $today)
            ->count();

        // Santri yang belum input mutaba'ah hari ini
        $sudahInput = KesantrianMutabaah::where('pesantren_id', $pesantrenId)
            ->whereIn('santri_id', $santriHalaqah)
            ->where('tanggal', $today)
            ->pluck('santri_id');

        $belumInput = $totalHalaqah - $sudahInput->count();

        // Santri sakit di halaqahnya hari ini
        $santriSakit = KesantrianKesehatan::where('pesantren_id', $pesantrenId)
            ->whereIn('santri_id', $santriHalaqah)
            ->where('tanggal_periksa', $today)
            ->whereIn('status_pemulihan', ['Istirahat_Total', 'Rujukan_Luar'])
            ->count();

        return [
            Stat::make('Santri Halaqah', $totalHalaqah)
                ->description('Santri binaan Anda')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Setoran Hari Ini', $setoranHariIni)
                ->description('Total setoran masuk hari ini')
                ->descriptionIcon('heroicon-m-book-open')
                ->color($setoranHariIni > 0 ? 'success' : 'warning'),

            Stat::make('Belum Input Mutaba\'ah', $belumInput)
                ->description('Dari ' . $totalHalaqah . ' santri halaqah')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($belumInput === 0 ? 'success' : ($belumInput <= 3 ? 'warning' : 'danger')),

            Stat::make('Santri Sakit', $santriSakit)
                ->description('Istirahat total & rujukan luar')
                ->descriptionIcon('heroicon-m-heart')
                ->color($santriSakit > 0 ? 'danger' : 'success'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Pesantren;
use App\Models\Santri;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SuperAdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $totalAktif = Pesantren::withoutGlobalScopes()
            ->where('status_berlangganan', 'active')
            ->count();

        $totalTrial = Pesantren::withoutGlobalScopes()
            ->where('status_berlangganan', 'trial')
            ->count();

        $totalSantri = Santri::withoutGlobalScopes()
            ->where('status_aktif', true)
            ->count();

        $pesantrenExpired = Pesantren::withoutGlobalScopes()
            ->where('status_berlangganan', 'expired')
            ->count();

        $pesantrenSuspended = Pesantren::withoutGlobalScopes()
            ->where('status_berlangganan', 'suspended')
            ->count();

        $expiringSoon = Pesantren::withoutGlobalScopes()
            ->where('status_berlangganan', 'active')
            ->whereBetween('expired_at', [now(), now()->addDays(7)])
            ->count();

        return [
            Stat::make('Pesantren Aktif', $totalAktif)
                ->description($totalTrial . ' pesantren trial')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Total Santri', $totalSantri)
                ->description('Di seluruh pesantren aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Akan Expired', $expiringSoon)
                ->description('Dalam 7 hari ke depan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'success'),

            Stat::make('Bermasalah', $pesantrenExpired + $pesantrenSuspended)
                ->description(
                    $pesantrenExpired . ' expired · ' .
                    $pesantrenSuspended . ' suspended'
                )
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($pesantrenExpired + $pesantrenSuspended) > 0 ? 'danger' : 'success'),
        ];
    }
}

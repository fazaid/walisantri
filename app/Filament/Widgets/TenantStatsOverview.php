<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Pesantren;
use App\Models\Santri;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantStatsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    protected function getStats(): array
    {
        $aktif = Pesantren::withoutGlobalScope('pesantren')
            ->whereIn('status_berlangganan', ['active', 'trial'])
            ->count();

        $totalSantri = Santri::allTenants()->count();

        $suspended = Pesantren::withoutGlobalScope('pesantren')
            ->where('status_berlangganan', 'suspended')
            ->count();

        $expired = Pesantren::withoutGlobalScope('pesantren')
            ->where('status_berlangganan', 'expired')
            ->count();

        return [
            Stat::make('Pesantren Aktif', $aktif)
                ->description('Status active & trial')
                ->color('success'),

            Stat::make('Total Santri', $totalSantri)
                ->description('Seluruh sistem')
                ->color('info'),

            Stat::make('Pesantren Suspended', $suspended)
                ->description('Akses diblokir')
                ->color('danger'),

            Stat::make('Pesantren Expired', $expired)
                ->description('Berlangganan habis')
                ->color('warning'),
        ];
    }
}

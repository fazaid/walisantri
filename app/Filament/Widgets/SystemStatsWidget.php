<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total User', User::count())
                ->description('Semua role')
                ->color('primary'),

            Stat::make('Total Ustadz', User::where('role', UserRole::Ustadz->value)->count())
                ->description('Role ustadz')
                ->color('success'),

            Stat::make('Total Wali Santri', User::where('role', UserRole::WaliSantri->value)->count())
                ->description('Role wali santri')
                ->color('info'),
        ];
    }
}

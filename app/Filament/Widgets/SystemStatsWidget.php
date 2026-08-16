<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SystemStatsWidget extends StatsOverviewWidget
{
    // Matikan polling default CanPoll (5s): dashboard agregat tak perlu refresh live,
    // dan request polling latar jadi sumber toast error saat wake-from-sleep.
    protected ?string $pollingInterval = null;

    // Tanpa $sort eksplisit, Filament memakai default -1 (Widget::getSort()), sehingga
    // widget ini justru terender DI ATAS SuperAdminStatsOverview yang ber-sort 1.
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    protected function getStats(): array
    {
        // User milik tenant sandbox bukan pengguna pelanggan. Super admin
        // ber-pesantren_id null tetap terhitung — dan itu memang benar.
        $pelanggan = fn () => User::whereDoesntHave(
            'pesantren',
            fn (Builder $q) => $q->where('is_demo', true)
        );

        return [
            Stat::make('Total User', $pelanggan()->count())
                ->description('Semua role')
                ->color('primary'),

            Stat::make('Total Ustadz', $pelanggan()->where('role', UserRole::Ustadz->value)->count())
                ->description('Role ustadz')
                ->color('success'),

            Stat::make('Total Wali Santri', $pelanggan()->where('role', UserRole::WaliSantri->value)->count())
                ->description('Role wali santri')
                ->color('info'),
        ];
    }
}

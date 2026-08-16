<?php

namespace App\Filament\Widgets;

use App\Enums\StatusBerlangganan;
use App\Models\Pesantren;
use App\Models\Santri;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SuperAdminStatsOverview extends StatsOverviewWidget
{
    // Matikan polling default CanPoll (5s): dashboard agregat tak perlu refresh live,
    // dan request polling latar jadi sumber toast error saat wake-from-sleep.
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $totalAktif = Pesantren::withoutGlobalScope('pesantren')->pelanggan()
            ->where('status_berlangganan', 'active')
            ->count();

        $totalTrial = Pesantren::withoutGlobalScope('pesantren')->pelanggan()
            ->where('status_berlangganan', 'trial')
            ->count();

        // withoutGlobalScope('pesantren'), BUKAN withoutGlobalScopes(): yang terakhir
        // ikut mencopot SoftDeletingScope, sehingga santri yang sudah dihapus (dan
        // status_aktif-nya tidak pernah diubah SantriObserver) ikut terhitung.
        $totalSantri = Santri::withoutGlobalScope('pesantren')
            ->where('status_aktif', true)
            // Santri dummy milik tenant sandbox bukan santri pelanggan.
            ->whereHas('pesantren', fn (Builder $q) => $q->where('is_demo', false))
            ->count();

        $pesantrenExpired = Pesantren::withoutGlobalScope('pesantren')->pelanggan()
            ->where('status_berlangganan', 'expired')
            ->count();

        $pesantrenSuspended = Pesantren::withoutGlobalScope('pesantren')->pelanggan()
            ->where('status_berlangganan', 'suspended')
            ->count();

        // Patokan status mengikuti kedua job latar yang benar-benar bertindak atas
        // tenant ini (WarnExpiringTenants & CheckExpiredTenants): trial DAN active.
        // Dulu hanya 'active', sehingga kartu ini melewatkan justru mayoritasnya —
        // pendaftar baru berstatus trial — dan angkanya tidak cocok dengan tabel
        // "Pesantren Akan Expired" tepat di bawahnya.
        $expiringSoon = Pesantren::withoutGlobalScope('pesantren')->pelanggan()
            ->whereIn('status_berlangganan', StatusBerlangganan::berjalan())
            ->whereBetween('expired_at', [now(), now()->addDays(7)])
            ->count();

        return [
            Stat::make('Pesantren Aktif', $totalAktif)
                ->description($totalTrial.' pesantren trial')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Total Santri', $totalSantri)
                ->description('Aktif di seluruh pesantren')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Akan Expired', $expiringSoon)
                ->description('Dalam 7 hari ke depan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'success'),

            Stat::make('Bermasalah', $pesantrenExpired + $pesantrenSuspended)
                ->description(
                    $pesantrenExpired.' expired · '.
                    $pesantrenSuspended.' suspended'
                )
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($pesantrenExpired + $pesantrenSuspended) > 0 ? 'danger' : 'success'),
        ];
    }
}

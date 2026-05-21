<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\ExpiringTenantsWidget;
use App\Filament\Widgets\SystemStatsWidget;
use App\Filament\Widgets\TenantListWidget;
use App\Filament\Widgets\TenantStatsOverview;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CentralDashboard extends Dashboard
{
    // Pisahkan dari route root '/' milik Dashboard bawaan Filament
    protected static string $routePath = 'central-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $navigationLabel = 'Dashboard Central';

    protected static ?string $title = 'Dashboard Central Walisantri';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function getWidgets(): array
    {
        return [
            TenantStatsOverview::class,
            SystemStatsWidget::class,
            ExpiringTenantsWidget::class,
            TenantListWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}

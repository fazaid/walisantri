<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Keuangan extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    // Top-level (bukan grup Manajemen) supaya tidak tertukar dengan menu "Langganan"
    // yang mengurus tagihan pesantren ke platform. Sort 6 = tepat di bawah Rapor (5).
    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Keuangan';

    protected static ?int $navigationSort = 6;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}

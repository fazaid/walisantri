<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PengaturanPesantren extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $slug = 'pengaturan';

    protected static ?int $navigationSort = 3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}

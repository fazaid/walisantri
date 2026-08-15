<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Presensi extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Presensi';

    /**
     * Sort 3 adalah slot yang kosong sejak Cluster Mutabaah dibubarkan di v4.19,
     * dan urutannya kebetulan benar secara alur kerja: Santri(0) → Akademik(1) →
     * Tahfidz(2) → Presensi(3) → Kesantrian(4). Presensi duduk di antara
     * pengajaran dan pembinaan, persis tempatnya dalam hari kerja pondok.
     */
    protected static ?int $navigationSort = 3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}

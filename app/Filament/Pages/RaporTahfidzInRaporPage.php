<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Rapor;

class RaporTahfidzInRaporPage extends RaporTahfidzPage
{
    protected static ?string $cluster = Rapor::class;

    protected static ?string $navigationLabel = 'Tahfidz';

    protected static ?int $navigationSort = 2;
}

<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\MedicalRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Santri', Student::count())
                ->description('Total seluruh santri terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Santri Laki-laki', Student::where('jenis_kelamin', 'L')->count())
                ->description('Jumlah Santri Putra')
                ->color('info'),

            Stat::make('Santri Perempuan', Student::where('jenis_kelamin', 'P')->count())
                ->description('Jumlah Santri Putri')
                ->color('warning'),

            Stat::make('Riwayat Sakit', MedicalRecord::count())
                ->description('Total catatan medis tercatat')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),
        ];
    }
}

<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors\Schemas;

use App\Models\Santri;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianMutabaahRaporForm
{
    public static function configure(Schema $schema): Schema
    {
        $tahunSekarang = (int) date('Y');
        $tahunOptions = [];
        for ($y = $tahunSekarang; $y >= $tahunSekarang - 3; $y--) {
            $tahunOptions[(string) $y] = (string) $y;
        }

        return $schema->components([
            Section::make('Periode Rapor')
                ->columns(3)
                ->schema([
                    Select::make('santri_id')
                        ->label('Santri')
                        ->options(function () {
                            $query = Santri::where('status_aktif', true);
                            if (auth()->user()?->role === 'ustadz') {
                                $query->where('pembimbing_ustadz_id', auth()->id());
                            }
                            return $query->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id');
                        })
                        ->searchable()
                        ->required(),

                    Select::make('bulan')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->default((int) date('m'))
                        ->required(),

                    Select::make('tahun')
                        ->label('Tahun')
                        ->options($tahunOptions)
                        ->default((string) $tahunSekarang)
                        ->required(),
                ]),

            Section::make('Catatan')->schema([
                Textarea::make('catatan')
                    ->label('Catatan (opsional)')
                    ->rows(3)
                    ->placeholder('Catatan tambahan dari ustadz...'),
            ]),
        ]);
    }
}

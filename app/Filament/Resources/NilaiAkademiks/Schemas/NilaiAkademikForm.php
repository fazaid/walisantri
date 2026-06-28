<?php

namespace App\Filament\Resources\NilaiAkademiks\Schemas;

use App\Models\MataPelajaran;
use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NilaiAkademikForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Penilaian')
                ->columns(2)
                ->schema([
                    Select::make('mata_pelajaran_id')
                        ->label('Mata Pelajaran')
                        ->options(function () {
                            $query = MataPelajaran::with('kelas')
                                ->where('pesantren_id', auth()->user()?->pesantren_id);

                            if (auth()->user()?->role === 'ustadz') {
                                $query->where('ustadz_id', auth()->id());
                            }

                            return $query->get()
                                ->mapWithKeys(fn (MataPelajaran $mapel) => [
                                    $mapel->id => $mapel->nama_mapel . ' — ' . $mapel->kelas?->nama_kelas,
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('santri_id', null)),
                    Select::make('santri_id')
                        ->label('Santri')
                        ->options(function (callable $get) {
                            $mapel = MataPelajaran::find($get('mata_pelajaran_id'));

                            if (! $mapel) {
                                return [];
                            }

                            return Santri::where('kelas_id', $mapel->kelas_id)
                                ->where('status_aktif', true)
                                ->pluck('nama_lengkap', 'id');
                        })
                        ->disabled(fn (callable $get) => ! $get('mata_pelajaran_id'))
                        ->placeholder('Pilih mata pelajaran dulu')
                        ->searchable()
                        ->required(),
                    Select::make('tahun_ajaran')
                        ->label('Tahun Ajaran')
                        ->options(TahunAjaranOptions::options())
                        ->default(TahunAjaranOptions::current())
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('bulan', null))
                        ->required(),
                    Select::make('periode')
                        ->label('Periode')
                        ->options([
                            'Bulanan'         => 'Bulanan',
                            'Semester_Ganjil' => 'Semester Ganjil',
                            'Semester_Genap'  => 'Semester Genap',
                        ])
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('bulan', null))
                        ->required(),
                    Select::make('bulan')
                        ->label('Bulan')
                        ->options(function (callable $get) {
                            $tahunAjaran = $get('tahun_ajaran');
                            if (! $tahunAjaran) return [];

                            [$startYear, $endYear] = array_map('intval', explode('/', $tahunAjaran));
                            $nama = [
                                1=>'Januari', 2=>'Februari', 3=>'Maret',    4=>'April',
                                5=>'Mei',     6=>'Juni',     7=>'Juli',      8=>'Agustus',
                                9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
                            ];

                            $options = [];
                            for ($m = 7; $m <= 12; $m++) {
                                $options["{$m}-{$startYear}"] = $nama[$m] . ' ' . $startYear;
                            }
                            for ($m = 1; $m <= 6; $m++) {
                                $options["{$m}-{$endYear}"] = $nama[$m] . ' ' . $endYear;
                            }
                            return $options;
                        })
                        ->visible(fn (callable $get) => $get('periode') === 'Bulanan')
                        ->required(fn (callable $get) => $get('periode') === 'Bulanan'),
                ]),

            Section::make('Nilai')
                ->columns(1)
                ->schema([
                    TextInput::make('nilai')
                        ->label('Nilai (0-100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(3)
                        ->nullable(),
                ]),
        ]);
    }
}

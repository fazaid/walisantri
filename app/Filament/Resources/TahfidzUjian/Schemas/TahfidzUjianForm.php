<?php

namespace App\Filament\Resources\TahfidzUjian\Schemas;

use App\Models\Santri;
use App\Models\User;
use App\Services\TahunAjaranOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzUjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Ujian')
                    ->columns(3)
                    ->schema([
                        Select::make('santri_id')
                            ->label('Santri')
                            ->options(function () {
                                $query = Santri::where('status_aktif', true);
                                if (auth()->user()?->role === 'ustadz') {
                                    $query->where('pembimbing_ustadz_id', auth()->id());
                                }
                                return $query->pluck('nama_lengkap', 'id');
                            })
                            ->searchable()->required(),
                        Select::make('penguji_id')
                            ->label('Penguji')
                            ->options(
                                User::where('role', 'ustadz')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->pluck('name', 'id')
                            )
                            ->default(fn () => auth()->user()?->role === 'ustadz' ? auth()->id() : null)
                            ->searchable()->required(),
                        DatePicker::make('tanggal_ujian')
                            ->label('Tanggal Ujian')
                            ->default(now())
                            ->required(),
                        Select::make('target_juz')
                            ->label('Target Juz')
                            ->options(
                                collect(range(1, 30))->mapWithKeys(fn ($juz) => [$juz => "{$juz} Juz"])
                            )
                            ->searchable()->required(),
                        Select::make('status_kelulusan')
                            ->label('Status Kelulusan')
                            ->options(['Lulus' => 'Lulus', 'Mengulang' => 'Mengulang'])
                            ->required(),
                    ]),

                Section::make('Periode Rapor')
                    ->columns(3)
                    ->schema([
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
                                    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
                                    5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
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

                Section::make('Penilaian')
                    ->columns(4)
                    ->schema([
                        TextInput::make('nilai_hafalan')
                            ->label('Nilai Hafalan')
                            ->required(),
                        Select::make('nilai_tilawah')->label('Tilawah')
                            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])->required(),
                        Select::make('nilai_makhraj')->label('Makhraj')
                            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])->required(),
                        Select::make('nilai_tajwid')->label('Tajwid')
                            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])->required(),
                        Textarea::make('rekomendasi_pembimbing')
                            ->label('Rekomendasi Pembimbing')
                            ->rows(4)->required()->columnSpanFull(),
                    ]),
            ]);
    }
}

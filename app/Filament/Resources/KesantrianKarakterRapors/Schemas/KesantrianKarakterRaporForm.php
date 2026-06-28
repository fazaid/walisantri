<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Schemas;

use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class KesantrianKarakterRaporForm
{
    private static function nilaiSelect(string $field, string $label): Select
    {
        return Select::make($field)->label($label)
            ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'])
            ->default('B')->required();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')->columns(2)->schema([
                    Select::make('santri_id')->label('Santri')
                        ->options(function () {
                            $query = Santri::where('status_aktif', true);
                            if (auth()->user()?->role === 'ustadz') {
                                $query->where('pembimbing_ustadz_id', auth()->id());
                            }
                            return $query->pluck('nama_lengkap', 'id');
                        })
                        ->searchable()->required(),

                    Select::make('tahun_ajaran')
                        ->label('Tahun Ajaran')
                        ->options(TahunAjaranOptions::options())
                        ->default(TahunAjaranOptions::current())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('bulan', null)),

                    Select::make('periode')->label('Periode')
                        ->options([
                            'Bulanan'         => 'Bulanan',
                            'Semester_Ganjil' => 'Semester Ganjil',
                            'Semester_Genap'  => 'Semester Genap',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('bulan', null)),

                    Select::make('bulan')
                        ->label('Bulan')
                        ->options(function (Get $get) {
                            $tahunAjaran = $get('tahun_ajaran') ?: TahunAjaranOptions::current();
                            [$startYear, $endYear] = array_map('intval', explode('/', $tahunAjaran));

                            $nama = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
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
                        ->visible(fn (Get $get) => $get('periode') === 'Bulanan')
                        ->required(fn (Get $get) => $get('periode') === 'Bulanan')
                        ->native(false),

                    DatePicker::make('tanggal_input')
                        ->label('Tanggal Input')
                        ->default(now())
                        ->required(),
                ]),

                Section::make('Penilaian Adab')->columns(4)->schema([
                    self::nilaiSelect('adab_ustadz', 'Adab ke Ustadz'),
                    self::nilaiSelect('adab_tamu', 'Adab ke Tamu'),
                    self::nilaiSelect('adab_asrama', 'Adab Asrama'),
                    self::nilaiSelect('adab_kelas', 'Adab Kelas'),
                    self::nilaiSelect('adab_sholat', 'Adab Sholat'),
                    self::nilaiSelect('adab_quran', 'Adab Al-Quran'),
                    self::nilaiSelect('adab_minum', 'Adab Minum'),
                ]),

                Section::make('Penilaian Kepribadian')->columns(4)->schema([
                    self::nilaiSelect('kepribadian_tanggungjawab', 'Tanggung Jawab'),
                    self::nilaiSelect('kepribadian_kemandirian', 'Kemandirian'),
                    self::nilaiSelect('kepribadian_kepatuhan', 'Kepatuhan'),
                    self::nilaiSelect('kepribadian_kebersihan', 'Kebersihan'),
                    self::nilaiSelect('kepribadian_mengelola', 'Mengelola Diri'),
                    self::nilaiSelect('kepribadian_kepedulian', 'Kepedulian'),
                    self::nilaiSelect('kepribadian_empati', 'Empati'),
                    self::nilaiSelect('kepribadian_kebersamaan', 'Kebersamaan'),
                    self::nilaiSelect('kepribadian_kedisiplinan', 'Kedisiplinan'),
                ]),

                Section::make('Catatan')->schema([
                    Textarea::make('log_kasus_khusus')->label('Log Kasus Khusus')->rows(3)->nullable(),
                ]),
            ]);
    }
}

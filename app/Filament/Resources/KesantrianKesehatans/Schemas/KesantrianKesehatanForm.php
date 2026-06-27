<?php

namespace App\Filament\Resources\KesantrianKesehatans\Schemas;

use App\Models\KesantrianKesehatan;
use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class KesantrianKesehatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pemeriksaan')
                    ->columns(2)
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
                            ->searchable()
                            ->required(),
                        DatePicker::make('tanggal_periksa')
                            ->label('Tanggal Periksa')
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->required()
                            ->rules([
                                fn (Get $get, ?Model $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $santriId = $get('santri_id');
                                    if (! $santriId || ! $value) {
                                        return;
                                    }
                                    $exists = KesantrianKesehatan::where('santri_id', $santriId)
                                        ->where('tanggal_periksa', $value)
                                        ->where('pesantren_id', auth()->user()?->pesantren_id)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->exists();
                                    if ($exists) {
                                        $fail('Rekam medis untuk santri ini pada tanggal tersebut sudah ada.');
                                    }
                                },
                            ]),
                        TextInput::make('berat_badan')
                            ->label('Berat Badan (kg)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(200)
                            ->step(0.1)
                            ->nullable(),
                        TextInput::make('tinggi_badan')
                            ->label('Tinggi Badan (cm)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(250)
                            ->step(0.1)
                            ->nullable(),
                    ]),

                Section::make('Keluhan & Tindakan')
                    ->columns(1)
                    ->schema([
                        Select::make('kategori_keluhan')
                            ->label('Kategori Keluhan')
                            ->options([
                                'Demam'      => 'Demam',
                                'Batuk_Pilek'=> 'Batuk / Pilek',
                                'Sakit_Perut'=> 'Sakit Perut',
                                'Pusing'     => 'Pusing',
                                'Kulit_Gatal'=> 'Kulit Gatal',
                                'Luka_Fisik' => 'Luka Fisik',
                                'Lainnya'    => 'Lainnya',
                            ])
                            ->required(),
                        Textarea::make('detail_keluhan_teks')
                            ->label('Detail Keluhan')
                            ->rows(3)
                            ->nullable(),
                        Textarea::make('tindakan_dan_obat')
                            ->label('Tindakan & Obat')
                            ->rows(3)
                            ->required(),
                        Select::make('status_pemulihan')
                            ->label('Status Pemulihan')
                            ->options([
                                'Rawat_Mandiri'  => 'Rawat Mandiri',
                                'Istirahat_Total'=> 'Istirahat Total',
                                'Rujukan_Luar'   => 'Rujukan Luar',
                            ])
                            ->required(),
                    ]),
            ]);
    }
}
<?php

// File: app/Filament/Resources/KesantrianKesehatans/Schemas/KesantrianKesehatanForm.php

namespace App\Filament\Resources\KesantrianKesehatans\Schemas;

use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->options(
                                Santri::where('status_aktif', true)
                                    ->pluck('nama_lengkap', 'id')
                            )
                            ->searchable()
                            ->required(),
                        DatePicker::make('tanggal_periksa')
                            ->label('Tanggal Periksa')
                            ->default(now())
                            ->required(),
                        TextInput::make('berat_badan')
                            ->label('Berat Badan (kg)')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                        TextInput::make('tinggi_badan')
                            ->label('Tinggi Badan (cm)')
                            ->numeric()
                            ->minValue(1)
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
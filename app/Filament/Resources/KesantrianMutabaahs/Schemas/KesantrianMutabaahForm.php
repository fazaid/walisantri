<?php

// ============================================================
// FILE 1: app/Filament/Resources/KesantrianMutabaahs/Schemas/KesantrianMutabaahForm.php
// ============================================================

namespace App\Filament\Resources\KesantrianMutabaahs\Schemas;

use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianMutabaahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri & Tanggal')
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
                            ->searchable()->required(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(now())->required(),
                        TextInput::make('jamaah_5_waktu')
                            ->label('Shalat Jamaah (dari 5)')
                            ->numeric()->minValue(0)->maxValue(5)->default(5)->required(),
                        Select::make('status_udzur')
                            ->label('Status Udzur')
                            ->options([
                                'Tidak'        => 'Tidak',
                                'Sakit'        => 'Sakit',
                                'Haid'         => 'Haid',
                                'Izin_Pulang'  => 'Izin Pulang',
                                'Tugas_Pondok' => 'Tugas Pondok',
                            ])->default('Tidak')->required(),
                    ]),

                Section::make('Amalan Harian')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_rawatib')->label('Shalat Rawatib')->default(false),
                        Toggle::make('is_shalat_malam')->label('Shalat Malam')->default(false),
                        Toggle::make('is_dhuha')->label('Shalat Dhuha')->default(false),
                        Toggle::make('is_tilawah_1juz')->label('Tilawah 1 Juz')->default(false),
                        Toggle::make('is_infak')->label('Infak')->default(false),
                        Toggle::make('is_puasa')->label('Puasa Sunnah')->default(false),
                    ]),
            ]);
    }
}
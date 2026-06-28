<?php

// ============================================================
// INVENTARIS
// FILE 1: app/Filament/Resources/KesantrianInventaris/Schemas/KesantrianInventarisForm.php
// ============================================================

namespace App\Filament\Resources\KesantrianInventaris\Schemas;

use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianInventarisForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Barang')->columns(2)->schema([
                    Select::make('santri_id')->label('Santri')
                        ->options(function () {
                                $query = Santri::where('status_aktif', true);
                                if (auth()->user()?->role === 'ustadz') {
                                    $query->where('pembimbing_ustadz_id', auth()->id());
                                }
                                return $query->pluck('nama_lengkap', 'id');
                            })
                        ->searchable()->required(),
                    TextInput::make('nama_barang_umum')->label('Nama Barang')->required(),
                    TextInput::make('kode_unik_fisik')->label('Kode Unik Fisik')
                        ->placeholder('FZ-SRG-01')
                        ->required()
                        ->unique(
                            table: 'kesantrian_inventaris',
                            column: 'kode_unik_fisik',
                            modifyRuleUsing: fn ($rule) => $rule->where('pesantren_id', auth()->user()?->pesantren_id),
                            ignoreRecord: true,
                        ),
                    TextInput::make('kuota_regulasi_maksimal')->label('Kuota Maks')->numeric()->required(),
                    Select::make('kondisi_barang')->label('Kondisi')
                        ->options([
                            'Baik'        => 'Baik',
                            'Layak_Rusak' => 'Layak Pakai / Rusak Ringan',
                            'Hilang'      => 'Hilang',
                        ])->default('Baik')->required(),
                    DatePicker::make('tanggal_sidak_terakhir')->label('Tanggal Sidak Terakhir')->nullable(),
                ]),
            ]);
    }
}
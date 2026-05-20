<?php

// ============================================================
// FILE 2: app/Filament/Resources/KesantrianMutabaahs/Schemas/KesantrianMutabaahInfolist.php
// ============================================================

namespace App\Filament\Resources\KesantrianMutabaahs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianMutabaahInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri & Tanggal')->columns(2)->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('tanggal')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('jamaah_5_waktu')->label('Shalat Jamaah'),
                    TextEntry::make('status_udzur')->label('Status Udzur')
                        ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Tidak'        => 'success',
                            'Sakit'        => 'danger',
                            'Haid'         => 'warning',
                            'Izin_Pulang'  => 'info',
                            'Tugas_Pondok' => 'info',
                            default        => 'gray',
                        }),
                ]),
                Section::make('Amalan Harian')->columns(3)->schema([
                    IconEntry::make('is_rawatib')->label('Rawatib')->boolean(),
                    IconEntry::make('is_shalat_malam')->label('Shalat Malam')->boolean(),
                    IconEntry::make('is_dhuha')->label('Dhuha')->boolean(),
                    IconEntry::make('is_tilawah_1juz')->label('Tilawah 1 Juz')->boolean(),
                    IconEntry::make('is_infak')->label('Infak')->boolean(),
                    IconEntry::make('is_puasa')->label('Puasa Sunnah')->boolean(),
                ]),
            ]);
    }
}
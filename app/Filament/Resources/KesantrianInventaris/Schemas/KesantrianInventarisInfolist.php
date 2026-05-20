<?php

// ============================================================
// FILE 2: app/Filament/Resources/KesantrianInventaris/Schemas/KesantrianInventarisInfolist.php
// ============================================================

namespace App\Filament\Resources\KesantrianInventaris\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianInventarisInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Barang')->columns(2)->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('nama_barang_umum')->label('Nama Barang'),
                    TextEntry::make('kode_unik_fisik')->label('Kode Unik'),
                    TextEntry::make('kuota_regulasi_maksimal')->label('Kuota Maks'),
                    TextEntry::make('kondisi_barang')->label('Kondisi')
                        ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Baik'        => 'success',
                            'Layak_Rusak' => 'warning',
                            'Hilang'      => 'danger',
                        }),
                    TextEntry::make('tanggal_sidak_terakhir')->label('Sidak Terakhir')
                        ->date('d M Y')->placeholder('-'),
                ]),
            ]);
    }
}
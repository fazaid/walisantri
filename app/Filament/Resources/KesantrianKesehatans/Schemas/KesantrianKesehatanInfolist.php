<?php

// File: app/Filament/Resources/KesantrianKesehatans/Schemas/KesantrianKesehatanInfolist.php

namespace App\Filament\Resources\KesantrianKesehatans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianKesehatanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pemeriksaan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('santri.nama_lengkap')
                            ->label('Santri'),
                        TextEntry::make('tanggal_periksa')
                            ->label('Tanggal Periksa')
                            ->date('d M Y'),
                        TextEntry::make('berat_badan')
                            ->label('Berat Badan')
                            ->suffix(' kg')
                            ->placeholder('-'),
                        TextEntry::make('tinggi_badan')
                            ->label('Tinggi Badan')
                            ->suffix(' cm')
                            ->placeholder('-'),
                    ]),

                Section::make('Keluhan & Tindakan')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('kategori_keluhan')
                            ->label('Kategori Keluhan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Demam'       => 'danger',
                                'Batuk_Pilek' => 'warning',
                                'Sakit_Perut' => 'warning',
                                'Pusing'      => 'info',
                                'Kulit_Gatal' => 'info',
                                'Luka_Fisik'  => 'danger',
                                default       => 'gray',
                            }),
                        TextEntry::make('detail_keluhan_teks')
                            ->label('Detail Keluhan')
                            ->placeholder('Tidak ada detail'),
                        TextEntry::make('tindakan_dan_obat')
                            ->label('Tindakan & Obat'),
                        TextEntry::make('status_pemulihan')
                            ->label('Status Pemulihan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Rawat_Mandiri'   => 'success',
                                'Istirahat_Total' => 'warning',
                                'Rujukan_Luar'    => 'danger',
                            }),
                    ]),

                Section::make('Timestamps')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ]),
            ]);
    }
}
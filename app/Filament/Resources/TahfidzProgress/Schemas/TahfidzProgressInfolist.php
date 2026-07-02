<?php

// File: app/Filament/Resources/TahfidzProgress/Schemas/TahfidzProgressInfolist.php

namespace App\Filament\Resources\TahfidzProgress\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzProgressInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Setoran')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('santri.nama_lengkap')
                            ->label('Santri'),
                        TextEntry::make('ustadz.name')
                            ->label('Ustadz Pencatat'),
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('tipe_setoran')
                            ->label('Tipe Setoran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Sabaq'  => 'success',
                                'Sabqi'  => 'info',
                                'Manzil' => 'warning',
                            }),
                    ]),

                Section::make('Halaman yang Disetorkan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('halaman_mulai')
                            ->label('Halaman Mulai'),
                        TextEntry::make('halaman_selesai')
                            ->label('Halaman Selesai'),
                        TextEntry::make('nama_surah')
                            ->label('Surah (Referensi)')
                            ->placeholder('—'),
                    ]),

                Section::make('Penilaian')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('nilai_kelancaran')
                            ->label('Nilai Kelancaran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Mumtaz'        => 'success',
                                'Jayyid Jiddan' => 'info',
                                'Jayyid'        => 'warning',
                                'Maqbul'        => 'danger',
                            }),
                        TextEntry::make('catatan_evaluasi')
                            ->label('Catatan Evaluasi')
                            ->placeholder('Tidak ada catatan'),
                    ]),
            ]);
    }
}
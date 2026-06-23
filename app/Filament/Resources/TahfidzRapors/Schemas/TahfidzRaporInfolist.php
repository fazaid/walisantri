<?php

// ============================================================
// FILE 2: app/Filament/Resources/TahfidzRapors/Schemas/TahfidzRaporInfolist.php
// ============================================================

namespace App\Filament\Resources\TahfidzRapors\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzRaporInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Ujian')->columns(3)->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('penguji.name')->label('Penguji'),
                    TextEntry::make('tanggal_ujian')->label('Tanggal Ujian')->date('d M Y'),
                    TextEntry::make('target_juz')->label('Target Juz')
                        ->formatStateUsing(fn ($state) => $state ? "{$state} Juz" : '—'),
                    TextEntry::make('status_kelulusan')->label('Status Kelulusan')->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'Lulus'    => 'success',
                            'Mengulang'=> 'danger',
                            default    => 'gray',
                        }),
                ]),
                Section::make('Periode Rapor')->columns(2)->schema([
                    TextEntry::make('tahun_ajaran')->label('Tahun Ajaran'),
                    TextEntry::make('periode')->label('Periode')
                        ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state)),
                ]),
                Section::make('Penilaian')->columns(4)->schema([
                    TextEntry::make('nilai_hafalan')->label('Nilai Hafalan'),
                    TextEntry::make('nilai_tilawah')->label('Tilawah')->badge(),
                    TextEntry::make('nilai_makhraj')->label('Makhraj')->badge(),
                    TextEntry::make('nilai_tajwid')->label('Tajwid')->badge(),
                    TextEntry::make('rekomendasi_pembimbing')->label('Rekomendasi')->columnSpanFull(),
                ]),
            ]);
    }
}
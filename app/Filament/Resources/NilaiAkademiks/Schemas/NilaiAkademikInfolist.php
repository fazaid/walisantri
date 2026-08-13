<?php

namespace App\Filament\Resources\NilaiAkademiks\Schemas;

use App\Services\TahunAjaranOptions;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NilaiAkademikInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('santri.nama_lengkap')
                            ->label('Santri'),
                        TextEntry::make('mataPelajaran.nama_mapel')
                            ->label('Mata Pelajaran'),
                        TextEntry::make('mataPelajaran.kelas.nama_kelas')
                            ->label('Kelas'),
                        TextEntry::make('mataPelajaran.ustadz.name')
                            ->label('Ustadz Pengampu')
                            ->placeholder('— belum ditugaskan —'),
                    ]),

                Section::make('Periode')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('tahun_ajaran')
                            ->label('Tahun Ajaran'),
                        TextEntry::make('periode')
                            ->label('Periode')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Bulanan' => 'info',
                                'Semester_Ganjil' => 'warning',
                                'Semester_Genap' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(
                                fn (string $state): string => TahunAjaranOptions::periodeOptions()[$state] ?? $state
                            ),
                        TextEntry::make('bulan')
                            ->label('Bulan')
                            ->placeholder('—')
                            ->formatStateUsing(
                                fn (?string $state, $record): string => TahunAjaranOptions::bulanOptions($record->tahun_ajaran)[$state] ?? (string) $state
                            ),
                    ]),

                Section::make('Penilaian')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('nilai')
                            ->label('Nilai')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 85 => 'success',
                                $state >= 70 => 'info',
                                $state >= 60 => 'warning',
                                default => 'danger',
                            }),
                        // Kolom ini diisi lewat form tapi tidak pernah tampil
                        // di tabel — di sinilah satu-satunya tempat ia terbaca.
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan'),
                        TextEntry::make('created_at')
                            ->label('Diinput')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\PresensiJamPelajarans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PresensiJamPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('jam_ke')
                    ->label('Jam ke-')
                    ->numeric()
                    // Mulai dari 1, bukan 0: di tabel `presensi`, jam_ke = 0 sudah
                    // bermakna "presensi harian" (Presensi::HARIAN). Membolehkan 0
                    // di sini akan membuat presensi jam pelajaran menabrak baris
                    // harian santri yang sama pada unique (santri_id, tanggal, jam_ke).
                    ->minValue(1)
                    ->maxValue(20)
                    ->required()
                    ->helperText('Nomor urut jam pelajaran. Angka ini yang tersimpan di catatan presensi.'),

                TimePicker::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->seconds(false)
                    ->required(),

                TimePicker::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->seconds(false)
                    ->required(),

                TextInput::make('label')
                    ->label('Label (opsional)')
                    ->maxLength(50)
                    ->placeholder('Istirahat / Dzuhur')
                    ->helperText('Kosongkan untuk jam pelajaran biasa.'),

                Toggle::make('aktif')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Jam nonaktif tidak muncul sebagai pilihan saat mengisi presensi, tapi catatan lamanya tetap utuh.'),
            ]);
    }
}

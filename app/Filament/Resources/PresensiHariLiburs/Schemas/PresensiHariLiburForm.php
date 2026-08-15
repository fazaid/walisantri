<?php

namespace App\Filament\Resources\PresensiHariLiburs\Schemas;

use App\Services\TahunAjaranOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Form UBAH — satu hari, sesuai bentuk tabelnya.
 *
 * Form TAMBAH berbeda: ia menerima rentang, dan didefinisikan langsung di
 * ListPresensiHariLiburs karena hanya dipakai di sana. Membiarkan keduanya berbeda
 * disengaja — orang menambah libur dalam rentang, tapi mengoreksinya per hari.
 */
class PresensiHariLiburForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->required(),

                TextInput::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('Maulid Nabi / Libur Akhir Semester')
                    ->maxLength(150)
                    ->required(),

                Select::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options(TahunAjaranOptions::options())
                    ->default(TahunAjaranOptions::current())
                    ->required(),
            ]);
    }

    /** Komponen form TAMBAH — rentang tanggal, dikembangkan jadi baris harian saat disimpan. */
    public static function komponenRentang(): array
    {
        return [
            DatePicker::make('tanggal_mulai')
                ->label('Tanggal Mulai')
                ->native(false)
                ->closeOnDateSelection()
                ->required(),

            DatePicker::make('tanggal_selesai')
                ->label('Tanggal Selesai')
                ->native(false)
                ->closeOnDateSelection()
                ->required()
                ->helperText('Untuk libur sehari, isi tanggal yang sama dengan tanggal mulai.'),

            TextInput::make('keterangan')
                ->label('Keterangan')
                ->placeholder('Maulid Nabi / Libur Akhir Semester')
                ->maxLength(150)
                ->required(),

            Select::make('tahun_ajaran')
                ->label('Tahun Ajaran')
                ->options(TahunAjaranOptions::options())
                ->default(TahunAjaranOptions::current())
                ->required(),
        ];
    }
}

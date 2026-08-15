<?php

namespace App\Filament\Resources\PresensiIzins\Schemas;

use App\Enums\JenisIzin;
use App\Filament\Support\SantriOptions;
use App\Models\PresensiIzin;
use App\Support\Waktu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PresensiIzinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Santri & Rentang')
                    ->columns(2)
                    ->schema([
                        Select::make('santri_id')
                            ->label('Santri')
                            ->options(fn () => SantriOptions::aktifUntukPengguna())
                            ->searchable()
                            ->required(),

                        Select::make('jenis')
                            ->label('Jenis Izin')
                            ->options(JenisIzin::options())
                            ->default(JenisIzin::Sakit->value)
                            ->required(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->default(Waktu::hariIni())
                            ->native(false)
                            ->closeOnDateSelection()
                            ->required(),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->default(Waktu::hariIni())
                            ->native(false)
                            ->closeOnDateSelection()
                            ->required()
                            ->afterOrEqual('tanggal_mulai')
                            // Tabelnya sengaja tanpa unique — "beririsan" bukan
                            // kesetaraan yang bisa dinyatakan constraint. Tanpa
                            // penjagaan ini, dua izin beririsan akan saling menimpa
                            // baris presensi dan hasil akhirnya bergantung urutan
                            // persetujuan.
                            ->rule(function (Get $get, ?PresensiIzin $record) {
                                return function (string $atribut, $nilai, callable $gagal) use ($get, $record): void {
                                    $santriId = $get('santri_id');
                                    $mulai = $get('tanggal_mulai');

                                    if (! $santriId || ! $mulai || ! $nilai) {
                                        return;
                                    }

                                    $bentrok = PresensiIzin::beririsan(
                                        (int) $santriId,
                                        Carbon::parse($mulai)->toDateString(),
                                        Carbon::parse($nilai)->toDateString(),
                                        $record?->getKey(),
                                    )->first();

                                    if ($bentrok) {
                                        $gagal(sprintf(
                                            'Santri ini sudah punya izin %s pada %s – %s. Batalkan atau ubah izin itu lebih dulu.',
                                            $bentrok->jenis->label(),
                                            $bentrok->tanggal_mulai->translatedFormat('d M Y'),
                                            $bentrok->tanggal_selesai->translatedFormat('d M Y'),
                                        ));
                                    }
                                };
                            }),
                    ]),

                Section::make('Keterangan')
                    ->schema([
                        Textarea::make('alasan')
                            ->label('Alasan')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                    ]),
            ]);
    }
}

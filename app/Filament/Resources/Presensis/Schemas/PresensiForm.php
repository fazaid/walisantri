<?php

namespace App\Filament\Resources\Presensis\Schemas;

use App\Enums\StatusKehadiran;
use App\Filament\Support\SantriOptions;
use App\Models\Santri;
use App\Support\Waktu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PresensiForm
{
    public static function configure(Schema $schema): Schema
    {
        // ListRecords memaksa schema modal jadi 2 kolom kalau form tidak menentukan
        // sendiri, bikin Section cuma selebar separuh modal.
        return $schema
            ->columns(1)
            ->components([
                Section::make('Santri & Tanggal')
                    ->columns(2)
                    ->schema([
                        Select::make('santri_id')
                            ->label('Santri')
                            ->options(fn () => SantriOptions::aktifUntukPengguna())
                            ->searchable()
                            ->required()
                            ->live()
                            // kelas_id adalah SNAPSHOT, bukan turunan yang dibaca ulang
                            // saat rekap: santri bisa pindah kelas, dan rekap per kelas
                            // harus mencerminkan kelas saat presensi dicatat.
                            ->afterStateUpdated(fn ($state, callable $set) => $set(
                                'kelas_id',
                                $state ? Santri::find($state)?->kelas_id : null,
                            )),

                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(Waktu::hariIni())
                            ->maxDate(Waktu::akhirHariIni())
                            ->native(false)
                            ->closeOnDateSelection()
                            ->required(),
                    ]),

                Section::make('Kehadiran')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(StatusKehadiran::options())
                            ->default(StatusKehadiran::Hadir->value)
                            ->required()
                            ->live(),

                        TextInput::make('menit_terlambat')
                            ->label('Terlambat (menit)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1440)
                            ->visible(fn (Get $get) => $get('status') === StatusKehadiran::Terlambat->value),

                        TextInput::make('catatan')
                            ->label('Catatan')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\UangSakus\Schemas;

use App\Enums\JenisUangSaku;
use App\Models\Santri;
use App\Support\Waktu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UangSakuForm
{
    public static function configure(Schema $schema): Schema
    {
        // ->columns(1) wajib: ListRecords memaksa schema modal jadi 2 kolom,
        // tanpa ini field-field pendek berjejer setengah lebar.
        return $schema->columns(1)->components([
            Select::make('santri_id')
                ->label('Santri')
                ->options(function () {
                    return Santri::where('pesantren_id', auth()->user()?->pesantren_id)
                        ->where('status_aktif', true)
                        ->orderBy('nama_lengkap')
                        ->pluck('nama_lengkap', 'id');
                })
                ->searchable()
                ->required(),

            Select::make('jenis')
                ->label('Jenis Transaksi')
                ->options(JenisUangSaku::options())
                ->required(),

            TextInput::make('nominal')
                ->label('Nominal (Rp)')
                ->numeric()
                ->minValue(1)
                ->required(),

            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->default(Waktu::hariIni())
                ->required(),

            Textarea::make('keterangan')
                ->label('Keterangan')
                ->rows(2)
                ->nullable(),
        ]);
    }
}

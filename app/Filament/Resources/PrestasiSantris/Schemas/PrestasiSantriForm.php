<?php

namespace App\Filament\Resources\PrestasiSantris\Schemas;

use App\Enums\TingkatPrestasi;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrestasiSantriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Prestasi')
                ->columns(2)
                ->schema([
                    Select::make('santri_id')
                        ->label('Santri')
                        ->options(function () {
                            $query = Santri::where('status_aktif', true);
                            if (auth()->user()?->role === 'ustadz') {
                                $query->where('pembimbing_ustadz_id', auth()->id());
                            }
                            return $query->pluck('nama_lengkap', 'id');
                        })
                        ->searchable()
                        ->required(),

                    DatePicker::make('tanggal')
                        ->label('Tanggal Prestasi')
                        ->default(now())
                        ->required(),

                    TextInput::make('judul')
                        ->label('Judul / Nama Lomba')
                        ->placeholder('Juara 1 MTQ Cabang Tilawah')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('kategori')
                        ->label('Kategori')
                        ->options(PrestasiSantri::$kategoriOptions)
                        ->searchable()
                        ->required(),

                    Select::make('tingkat')
                        ->label('Tingkat')
                        ->options(TingkatPrestasi::options())
                        ->required(),

                    Select::make('posisi')
                        ->label('Posisi / Peringkat')
                        ->options(PrestasiSantri::$posisiOptions)
                        ->searchable()
                        ->nullable()
                        ->placeholder('Pilih posisi (opsional)'),

                    TextInput::make('penyelenggara')
                        ->label('Penyelenggara')
                        ->placeholder('Kemenag Kab. Bandung')
                        ->maxLength(200)
                        ->nullable(),

                    Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Dokumen')
                ->schema([
                    FileUpload::make('dokumen')
                        ->label('Sertifikat / Foto Piala (opsional)')
                        ->disk('public')
                        ->directory('prestasi')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxSize(5120)
                        ->nullable(),
                ]),
        ]);
    }
}

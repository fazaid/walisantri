<?php

namespace App\Filament\Resources\SantriEkskuls\Schemas;

use App\Filament\Support\SantriOptions;
use App\Support\Waktu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class SantriEkskulForm
{
    public static function configure(Schema $schema): Schema
    {
        // ListRecords memaksa schema modal jadi 2 kolom kalau form tidak
        // menentukan sendiri, bikin Section cuma selebar separuh modal.
        // columns(1) menahannya supaya tiap Section penuh selebar modal.
        return $schema
            ->columns(1)
            ->components([
                Section::make('Data Keikutsertaan')
                    ->columns(2)
                    ->schema([
                        Select::make('santri_id')
                            ->label('Santri')
                            // Daftarnya dimuat di muka, sama seperti sembilan form
                            // ber-santri lainnya. Versi sebelumnya memakai
                            // relationship()->searchable() tanpa preload() demi
                            // menghemat payload, tapi akibatnya dropdown ini kosong
                            // sampai admin mengetik — satu-satunya di seluruh panel
                            // yang berperilaku begitu, dan tidak ada cara menebaknya
                            // dari layar. Jumlah santri dibatasi kuota paket, jadi
                            // yang dihemat tidak sebanding dengan kebingungannya.
                            //
                            // SantriOptions::aktifUntukPengguna() memakai
                            // PenugasanUstadz::santriIdsBimbingan() — definisi cakupan
                            // ustadz yang sama persis dengan ScopesQueryToUstadzSantri
                            // di resource ini, jadi batasannya tidak berubah — dan
                            // Santri::query() di dalamnya tetap lewat global scope
                            // Multitenantable.
                            ->options(fn () => SantriOptions::aktifUntukPengguna())
                            ->searchable()
                            ->required(),
                        Select::make('ekskul_id')
                            ->label('Ekskul')
                            ->relationship(
                                'ekskulMaster',
                                'nama',
                                fn (Builder $query) => $query->where('aktif', true)->orderBy('nama'),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(
                                table: 'santri_ekskuls',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                                    ->where('santri_id', $get('santri_id')),
                            )
                            ->validationMessages([
                                'unique' => 'Santri ini sudah terdaftar di ekskul tersebut.',
                            ]),
                        Select::make('level')
                            ->label('Level')
                            ->options([
                                'pemula' => 'Pemula',
                                'menengah' => 'Menengah',
                                'mahir' => 'Mahir',
                            ])
                            ->default('pemula')
                            ->required(),
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->maxDate(Waktu::akhirHariIni())
                            ->native(false),
                        Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

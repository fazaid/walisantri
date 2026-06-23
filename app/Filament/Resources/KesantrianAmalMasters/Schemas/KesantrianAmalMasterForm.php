<?php

namespace App\Filament\Resources\KesantrianAmalMasters\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianAmalMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Amal')
                ->columns(2)
                ->schema([
                    TextInput::make('label')
                        ->label('Nama Amal')
                        ->placeholder('Contoh: Setor Hadits Harian')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('icon')
                        ->label('Ikon (emoji)')
                        ->placeholder('📿')
                        ->maxLength(10),
                    Select::make('tipe')
                        ->label('Tipe Penilaian')
                        ->options([
                            'boolean'  => 'Centang (dikerjakan / tidak)',
                            'hitungan' => 'Hitungan (misal 0-5 waktu)',
                        ])
                        ->default('boolean')
                        ->required()
                        ->live(),
                    TextInput::make('nilai_maks')
                        ->label('Nilai Maksimal')
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn (callable $get) => $get('tipe') === 'hitungan')
                        ->required(fn (callable $get) => $get('tipe') === 'hitungan'),
                    TextInput::make('satuan')
                        ->label('Satuan')
                        ->placeholder('hari / waktu / halaman')
                        ->default('hari')
                        ->required()
                        ->maxLength(20),
                    TextInput::make('bobot')
                        ->label('Bobot Poin')
                        ->helperText('Kontribusi maksimal amal ini terhadap skor harian (skala bebas, semakin besar semakin berpengaruh).')
                        ->numeric()
                        ->minValue(1)
                        ->default(7)
                        ->required(),
                    TextInput::make('urutan')
                        ->label('Urutan Tampil')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make('aktif')
                        ->label('Aktif')
                        ->helperText('Amal nonaktif tidak akan muncul di form input maupun dihitung ke skor harian, tapi riwayat data lama tetap tersimpan.')
                        ->default(true),
                    TextInput::make('kode')
                        ->label('Kode (kunci data, tidak bisa diubah)')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                ]),
        ]);
    }
}

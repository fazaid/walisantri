<?php

namespace App\Filament\Resources\SantriEkskuls\Schemas;

use App\Models\EkskulMaster;
use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SantriEkskulForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Keikutsertaan')
                ->columns(2)
                ->schema([
                    Select::make('santri_id')
                        ->label('Santri')
                        ->options(
                            Santri::where('pesantren_id', auth()->user()?->pesantren_id)
                                ->where('status_aktif', true)
                                ->orderBy('nama_lengkap')
                                ->pluck('nama_lengkap', 'id')
                        )
                        ->searchable()
                        ->required(),
                    Select::make('ekskul_id')
                        ->label('Ekskul')
                        ->options(
                            EkskulMaster::where('pesantren_id', auth()->user()?->pesantren_id)
                                ->where('aktif', true)
                                ->orderBy('nama')
                                ->pluck('nama', 'id')
                        )
                        ->searchable()
                        ->required(),
                    Select::make('level')
                        ->label('Level')
                        ->options([
                            'pemula'   => 'Pemula',
                            'menengah' => 'Menengah',
                            'mahir'    => 'Mahir',
                        ])
                        ->default('pemula')
                        ->required(),
                    DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->maxDate(now())
                        ->native(false),
                    Toggle::make('aktif')
                        ->label('Aktif')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

<?php

// File: app/Filament/Resources/Santris/Schemas/SantriForm.php

namespace App\Filament\Resources\Santris\Schemas;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\User;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SantriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        TextInput::make('nama_lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn () => Kelas::where('pesantren_id', auth()->user()?->pesantren_id)
                                ->orderBy('nama_kelas')
                                ->pluck('nama_kelas', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false),
                        Select::make('kamar_id')
                            ->label('Kamar')
                            ->options(fn () => Kamar::where('pesantren_id', auth()->user()?->pesantren_id)
                                ->orderBy('nama_kamar')
                                ->pluck('nama_kamar', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false),
                        Toggle::make('status_aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Relasi')
                    ->columns(2)
                    ->schema([
                        Select::make('wali_santri_id')
                            ->label('Wali Santri')
                            ->options(
                                User::where('role', 'wali_santri')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                        Select::make('pembimbing_ustadz_id')
                            ->label('Ustadz Pembimbing')
                            ->options(
                                User::where('role', 'ustadz')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                    ]),
            ]);
    }
}
<?php

// File: app/Filament/Resources/TahfidzProgress/Schemas/TahfidzProgressForm.php

namespace App\Filament\Resources\TahfidzProgress\Schemas;

use App\Models\Santri;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Setoran')
                    ->columns(2)
                    ->schema([
                        Select::make('santri_id')
                            ->label('Santri')
                            ->options(
                                Santri::where('status_aktif', true)
                                    ->pluck('nama_lengkap', 'id')
                            )
                            ->searchable()
                            ->required(),
                        Select::make('ustadz_id')
                            ->label('Ustadz Pencatat')
                            ->options(
                                User::where('role', 'ustadz')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal Setoran')
                            ->default(now())
                            ->required(),
                        Select::make('tipe_setoran')
                            ->label('Tipe Setoran')
                            ->options([
                                'Sabaq'  => 'Sabaq (Hafalan Baru)',
                                'Sabqi'  => 'Sabqi (Hafalan Kemarin)',
                                'Manzil' => 'Manzil (Hafalan Lama)',
                            ])
                            ->required(),
                    ]),

                Section::make('Detail Ayat')
                    ->columns(3)
                    ->schema([
                        TextInput::make('nama_surah')
                            ->label('Nama Surah')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('ayat_mulai')
                            ->label('Ayat Mulai')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('ayat_selesai')
                            ->label('Ayat Selesai')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),

                Section::make('Penilaian')
                    ->columns(1)
                    ->schema([
                        Select::make('nilai_kelancaran')
                            ->label('Nilai Kelancaran')
                            ->options([
                                'Mumtaz'       => 'Mumtaz (Sangat Baik)',
                                'Jayyid Jiddan'=> 'Jayyid Jiddan (Baik Sekali)',
                                'Jayyid'       => 'Jayyid (Baik)',
                                'Maqbul'       => 'Maqbul (Cukup)',
                            ])
                            ->required(),
                        Textarea::make('catatan_evaluasi')
                            ->label('Catatan Evaluasi')
                            ->rows(3)
                            ->nullable(),
                    ]),
            ]);
    }
}
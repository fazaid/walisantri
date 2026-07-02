<?php

namespace App\Filament\Resources\TahfidzProgress\Schemas;

use App\Data\QuranSurah;
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
        return $schema->components([
            Section::make('Detail Setoran')
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
                    Select::make('ustadz_id')
                        ->label('Ustadz Pencatat')
                        ->options(
                            User::where('role', 'ustadz')
                                ->where('pesantren_id', auth()->user()?->pesantren_id)
                                ->pluck('name', 'id')
                        )
                        ->default(fn () => auth()->user()?->role === 'ustadz' ? auth()->id() : null)
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

            Section::make('Halaman yang Disetorkan')
                ->description('Surah terakhir yang disetorkan bersifat opsional.')
                ->columns(3)
                ->schema([
                    TextInput::make('halaman_mulai')
                        ->label('Halaman Mulai')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(600)
                        ->required(),
                    TextInput::make('halaman_selesai')
                        ->label('Halaman Selesai')
                        ->numeric()
                        ->minValue(fn ($get) => (int) ($get('halaman_mulai') ?: 1))
                        ->maxValue(600)
                        ->required()
                        ->helperText('Maks: hal. 600 (Juz 30)'),
                    Select::make('nama_surah')
                        ->label('Surah Terakhir Disetorkan')
                        ->options(QuranSurah::options())
                        ->searchable()
                        ->optionsLimit(114)
                        ->native(false)
                        ->placeholder('Opsional'),
                ]),

            Section::make('Penilaian')
                ->columns(1)
                ->schema([
                    Select::make('nilai_kelancaran')
                        ->label('Nilai Kelancaran')
                        ->options([
                            'Mumtaz'        => 'Mumtaz (Sangat Baik)',
                            'Jayyid Jiddan' => 'Jayyid Jiddan (Baik Sekali)',
                            'Jayyid'        => 'Jayyid (Baik)',
                            'Maqbul'        => 'Maqbul (Cukup)',
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

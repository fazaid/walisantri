<?php

// File: app/Filament/Resources/TahfidzProgress/Schemas/TahfidzProgressForm.php

namespace App\Filament\Resources\TahfidzProgress\Schemas;

use App\Data\QuranSurah;
use App\Models\Santri;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzProgressForm
{
    public static function configureCreate(Schema $schema): Schema
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
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Surah yang Disetorkan')
                ->schema([
                    Repeater::make('surahs')
                        ->label('')
                        ->addActionLabel('+ Tambah Surah')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->columns(3)
                        ->schema([
                            Select::make('nama_surah')
                                ->label('Nama Surah')
                                ->options(QuranSurah::options())
                                ->searchable()
                                ->optionsLimit(114)
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('ayat_mulai', 1);
                                    $set('ayat_selesai', null);
                                }),
                            TextInput::make('ayat_mulai')
                                ->label('Ayat Mulai')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(fn ($get) => $get('nama_surah')
                                    ? QuranSurah::ayatCount($get('nama_surah'))
                                    : 286)
                                ->required(),
                            TextInput::make('ayat_selesai')
                                ->label('Ayat Selesai')
                                ->numeric()
                                ->minValue(fn ($get) => (int) ($get('ayat_mulai') ?: 1))
                                ->maxValue(fn ($get) => $get('nama_surah')
                                    ? QuranSurah::ayatCount($get('nama_surah'))
                                    : 286)
                                ->required()
                                ->helperText(fn ($get) => $get('nama_surah')
                                    ? 'Maks: ayat ' . QuranSurah::ayatCount($get('nama_surah'))
                                    : ''),
                        ]),
                ]),
        ]);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                Section::make('Detail Ayat')
                    ->columns(3)
                    ->schema([
                        Select::make('nama_surah')
                            ->label('Nama Surah')
                            ->options(QuranSurah::options())
                            ->searchable()
                            ->optionsLimit(114)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('ayat_mulai', 1);
                                $set('ayat_selesai', null);
                            }),
                        TextInput::make('ayat_mulai')
                            ->label('Ayat Mulai')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn ($get) => $get('nama_surah')
                                ? QuranSurah::ayatCount($get('nama_surah'))
                                : 286)
                            ->required(),
                        TextInput::make('ayat_selesai')
                            ->label('Ayat Selesai')
                            ->numeric()
                            ->minValue(fn ($get) => (int) ($get('ayat_mulai') ?: 1))
                            ->maxValue(fn ($get) => $get('nama_surah')
                                ? QuranSurah::ayatCount($get('nama_surah'))
                                : 286)
                            ->required()
                            ->helperText(fn ($get) => $get('nama_surah')
                                ? 'Maks: ayat ' . QuranSurah::ayatCount($get('nama_surah'))
                                : ''),
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

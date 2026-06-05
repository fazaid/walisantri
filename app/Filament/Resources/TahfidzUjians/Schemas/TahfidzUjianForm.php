<?php

// ============================================================
// FILE 2: app/Filament/Resources/TahfidzUjians/Schemas/TahfidzUjianForm.php
// ============================================================

namespace App\Filament\Resources\TahfidzUjians\Schemas;

use App\Models\Santri;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzUjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Ujian')
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
                        Select::make('penguji_id')
                            ->label('Penguji')
                            ->options(
                                User::where('role', 'ustadz')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->pluck('name', 'id')
                            )
                            ->default(fn () => auth()->user()?->role === 'ustadz' ? auth()->id() : null)
                            ->searchable()
                            ->required(),
                        DatePicker::make('tanggal_ujian')
                            ->label('Tanggal Ujian')
                            ->default(now())
                            ->required(),
                        Select::make('target_juz')
                            ->label('Target Juz')
                            ->options(array_combine(
                                ['1','3','5','10','15','20','25','30'],
                                ['1 Juz','3 Juz','5 Juz','10 Juz','15 Juz','20 Juz','25 Juz','30 Juz']
                            ))
                            ->required(),
                        Select::make('status_kelulusan')
                            ->label('Status Kelulusan')
                            ->options(['Lulus' => 'Lulus', 'Mengulang' => 'Mengulang'])
                            ->required(),
                        Textarea::make('catatan_ujian')
                            ->label('Catatan Ujian')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
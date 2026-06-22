<?php
// ============================================================
// FILE 1: app/Filament/Resources/TahfidzRapors/Schemas/TahfidzRaporForm.php
// ============================================================

namespace App\Filament\Resources\TahfidzRapors\Schemas;

use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzRaporForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Rapor')
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
                            ->searchable()->required(),
                        Select::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->options(TahunAjaranOptions::options())
                            ->default(TahunAjaranOptions::current())
                            ->required(),
                        Select::make('periode')
                            ->label('Periode')
                            ->options([
                                'Bulanan'        => 'Bulanan',
                                'Semester_Ganjil'=> 'Semester Ganjil',
                                'Semester_Genap' => 'Semester Genap',
                            ])->required(),
                        TextInput::make('nilai_hafalan')
                            ->label('Nilai Hafalan')
                            ->required(),
                    ]),

                Section::make('Penilaian')
                    ->columns(3)
                    ->schema([
                        Select::make('nilai_tilawah')->label('Tilawah')
                            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])->required(),
                        Select::make('nilai_makhraj')->label('Makhraj')
                            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])->required(),
                        Select::make('nilai_tajwid')->label('Tajwid')
                            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])->required(),
                        Textarea::make('rekomendasi_pembimbing')
                            ->label('Rekomendasi Pembimbing')
                            ->rows(4)->required()->columnSpanFull(),
                    ]),
            ]);
    }
}
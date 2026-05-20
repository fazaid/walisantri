<?php

// ============================================================
// FILE 1: app/Filament/Resources/KesantrianKarakterRapors/Schemas/KesantrianKarakterRaporForm.php
// ============================================================

namespace App\Filament\Resources\KesantrianKarakterRapors\Schemas;

use App\Models\Santri;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianKarakterRaporForm
{
    // Helper untuk membuat kolom nilai A/B/C/D
    private static function nilaiSelect(string $field, string $label): Select
    {
        return Select::make($field)->label($label)
            ->options(['A'=>'A','B'=>'B','C'=>'C','D'=>'D'])
            ->default('B')->required();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')->columns(2)->schema([
                    Select::make('santri_id')->label('Santri')
                        ->options(Santri::where('status_aktif', true)->pluck('nama_lengkap', 'id'))
                        ->searchable()->required(),
                    DatePicker::make('tanggal_input')->label('Tanggal Input')->default(now())->required(),
                    Select::make('periode')->label('Periode')
                        ->options(['Bulanan'=>'Bulanan','Semester'=>'Semester'])->required(),
                ]),

                Section::make('Penilaian Adab')->columns(4)->schema([
                    self::nilaiSelect('adab_ustadz', 'Adab ke Ustadz'),
                    self::nilaiSelect('adab_tamu', 'Adab ke Tamu'),
                    self::nilaiSelect('adab_asrama', 'Adab Asrama'),
                    self::nilaiSelect('adab_kelas', 'Adab Kelas'),
                    self::nilaiSelect('adab_sholat', 'Adab Sholat'),
                    self::nilaiSelect('adab_quran', 'Adab Al-Quran'),
                    self::nilaiSelect('adab_minum', 'Adab Minum'),
                ]),

                Section::make('Penilaian Kepribadian')->columns(4)->schema([
                    self::nilaiSelect('kepribadian_tanggungjawab', 'Tanggung Jawab'),
                    self::nilaiSelect('kepribadian_kemandirian', 'Kemandirian'),
                    self::nilaiSelect('kepribadian_kepatuhan', 'Kepatuhan'),
                    self::nilaiSelect('kepribadian_kebersihan', 'Kebersihan'),
                    self::nilaiSelect('kepribadian_mengelola', 'Mengelola Diri'),
                    self::nilaiSelect('kepribadian_kepedulian', 'Kepedulian'),
                    self::nilaiSelect('kepribadian_empati', 'Empati'),
                    self::nilaiSelect('kepribadian_kebersamaan', 'Kebersamaan'),
                    self::nilaiSelect('kepribadian_kedisiplinan', 'Kedisiplinan'),
                ]),

                Section::make('Catatan')->schema([
                    Textarea::make('log_kasus_khusus')->label('Log Kasus Khusus')->rows(3)->nullable(),
                ]),
            ]);
    }
}
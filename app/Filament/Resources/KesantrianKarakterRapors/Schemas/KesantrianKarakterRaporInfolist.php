<?php

// ============================================================
// FILE 2: app/Filament/Resources/KesantrianKarakterRapors/Schemas/KesantrianKarakterRaporInfolist.php
// ============================================================

namespace App\Filament\Resources\KesantrianKarakterRapors\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KesantrianKarakterRaporInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')->columns(3)->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('tanggal_input')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('periode')->label('Periode'),
                ]),
                Section::make('Adab')->columns(4)->schema([
                    TextEntry::make('adab_ustadz')->label('Ustadz')->badge(),
                    TextEntry::make('adab_tamu')->label('Tamu')->badge(),
                    TextEntry::make('adab_asrama')->label('Asrama')->badge(),
                    TextEntry::make('adab_kelas')->label('Kelas')->badge(),
                    TextEntry::make('adab_sholat')->label('Sholat')->badge(),
                    TextEntry::make('adab_quran')->label('Al-Quran')->badge(),
                    TextEntry::make('adab_minum')->label('Minum')->badge(),
                ]),
                Section::make('Kepribadian')->columns(4)->schema([
                    TextEntry::make('kepribadian_tanggungjawab')->label('Tanggung Jawab')->badge(),
                    TextEntry::make('kepribadian_kemandirian')->label('Kemandirian')->badge(),
                    TextEntry::make('kepribadian_kepatuhan')->label('Kepatuhan')->badge(),
                    TextEntry::make('kepribadian_kebersihan')->label('Kebersihan')->badge(),
                    TextEntry::make('kepribadian_mengelola')->label('Mengelola Diri')->badge(),
                    TextEntry::make('kepribadian_kepedulian')->label('Kepedulian')->badge(),
                    TextEntry::make('kepribadian_empati')->label('Empati')->badge(),
                    TextEntry::make('kepribadian_kebersamaan')->label('Kebersamaan')->badge(),
                    TextEntry::make('kepribadian_kedisiplinan')->label('Kedisiplinan')->badge(),
                ]),
                Section::make('Catatan')->schema([
                    TextEntry::make('log_kasus_khusus')->label('Log Kasus Khusus')->placeholder('-'),
                ]),
            ]);
    }
}
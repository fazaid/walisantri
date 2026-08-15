<?php

namespace App\Filament\Resources\PresensiJamPelajarans;

use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Concerns\HasAdminOnlyAccess;
use App\Filament\Resources\PresensiJamPelajarans\Pages\ListPresensiJamPelajarans;
use App\Filament\Resources\PresensiJamPelajarans\Schemas\PresensiJamPelajaranForm;
use App\Filament\Resources\PresensiJamPelajarans\Tables\PresensiJamPelajaransTable;
use App\Models\PresensiJamPelajaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PresensiJamPelajaranResource extends Resource
{
    use HasAdminOnlyAccess;

    protected static ?string $model = PresensiJamPelajaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $modelLabel = 'Jam Pelajaran';

    protected static ?string $pluralModelLabel = 'Jam Pelajaran';

    /**
     * Menu Presensi tetap empat submenu (Kehadiran · Rekap · Hari Libur ·
     * Pengajuan Izin). Ini master data yang disentuh sekali saat menyiapkan
     * pesantren, bukan menu harian — jalan masuknya lewat tombol di halaman
     * Pengaturan Presensi dan halaman Isi Presensi per Jam.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'jam-pelajaran';

    protected static ?string $recordTitleAttribute = 'jam_ke';

    public static function form(Schema $schema): Schema
    {
        return PresensiJamPelajaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresensiJamPelajaransTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPresensiJamPelajarans::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\PresensiHariLiburs;

use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Concerns\HasAdminOnlyAccess;
use App\Filament\Resources\PresensiHariLiburs\Pages\ListPresensiHariLiburs;
use App\Filament\Resources\PresensiHariLiburs\Schemas\PresensiHariLiburForm;
use App\Filament\Resources\PresensiHariLiburs\Tables\PresensiHariLibursTable;
use App\Models\PresensiHariLibur;
use App\Support\Waktu;
use BackedEnum;
use Closure;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PresensiHariLiburResource extends Resource
{
    use HasAdminOnlyAccess;

    protected static ?string $model = PresensiHariLibur::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $navigationLabel = 'Hari Libur';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Kalender Hari Libur';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'hari-libur';

    protected static ?string $recordTitleAttribute = 'keterangan';

    /** Batas aman sekali simpan — libur sepanjang tahun pun tidak sampai segini. */
    public const MAKS_HARI_SEKALI_SIMPAN = 400;

    public static function form(Schema $schema): Schema
    {
        return PresensiHariLiburForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresensiHariLibursTable::configure($table);
    }

    /**
     * Menyimpan satu RENTANG tanggal sebagai N baris harian.
     *
     * Tabelnya sengaja satu baris per hari (lihat migrasi 2026_08_15_000005), tapi
     * cara orang memikirkan hari libur adalah rentang — "libur akhir semester
     * 20 Des–5 Jan", bukan tujuh belas entri terpisah. Pengembangan rentang jadi
     * baris harian terjadi di sini, satu kali, alih-alih membebani setiap pembaca
     * dengan logika tumpang-tindih rentang.
     *
     * updateOrCreate, bukan insert: menyimpan rentang yang beririsan dengan libur
     * yang sudah ada memperbarui keterangannya. Itu perilaku yang diharapkan admin
     * saat ia mengoreksi tanggal — bukan pelanggaran unique yang mentah ke layar.
     */
    public static function simpanRentang(): Closure
    {
        return function (array $data): Model {
            $mulai = Carbon::parse($data['tanggal_mulai']);
            $selesai = Carbon::parse($data['tanggal_selesai']);

            if ($selesai->lt($mulai)) {
                [$mulai, $selesai] = [$selesai, $mulai];
            }

            $jumlahHari = $mulai->diffInDays($selesai) + 1;

            if ($jumlahHari > self::MAKS_HARI_SEKALI_SIMPAN) {
                Notification::make()
                    ->title('Rentang terlalu panjang')
                    ->body('Rentang ini mencakup '.$jumlahHari.' hari. Maksimal '.self::MAKS_HARI_SEKALI_SIMPAN.' hari sekali simpan — periksa kembali tanggalnya.')
                    ->danger()
                    ->send();

                // Mengembalikan model kosong yang tidak tersimpan; CreateAction hanya
                // butuh sebuah Model, dan notifikasi di atas yang menjelaskan ke pengguna.
                return new PresensiHariLibur;
            }

            $pesantrenId = Auth::user()->pesantren_id;
            $terakhir = null;

            DB::transaction(function () use ($mulai, $selesai, $data, $pesantrenId, &$terakhir): void {
                for ($tanggal = $mulai->copy(); $tanggal->lte($selesai); $tanggal->addDay()) {
                    $terakhir = PresensiHariLibur::updateOrCreate(
                        [
                            'pesantren_id' => $pesantrenId,
                            'tanggal' => $tanggal->toDateString(),
                        ],
                        [
                            'keterangan' => $data['keterangan'],
                            'tahun_ajaran' => $data['tahun_ajaran'],
                        ],
                    );
                }
            });

            if ($jumlahHari > 1) {
                Notification::make()
                    ->title($jumlahHari.' hari libur tersimpan')
                    ->body('Rentang '.$mulai->translatedFormat('d M Y').' – '.$selesai->translatedFormat('d M Y').' dipecah menjadi '.$jumlahHari.' entri harian.')
                    ->success()
                    ->send();
            }

            return $terakhir ?? new PresensiHariLibur;
        };
    }

    /** Nilai awal form tambah — sehari saja, hari ini. */
    public static function isianAwalRentang(): array
    {
        return [
            'tanggal_mulai' => Waktu::hariIni(),
            'tanggal_selesai' => Waktu::hariIni(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPresensiHariLiburs::route('/'),
        ];
    }
}

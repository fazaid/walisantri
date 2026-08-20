<?php

namespace App\Enums;

use App\Models\ModulPengaturan;
use Illuminate\Support\Facades\Auth;

/**
 * Modul yang bisa dimatikan sendiri oleh sebuah pesantren.
 *
 * ⚠️ INI BUKAN FEATURE LOCK BERBASIS PAKET. Kelima Gate `access-modul-*` dihapus di
 * v4.20 karena tidak pernah sekali pun dipanggil, dan "Feature lock berbasis paket"
 * tetap duduk di daftar di-skip §22. Yang diputuskan di sini adalah preferensi
 * TENANT — admin pesantren memilih keluar dari modul yang memang tidak ia pakai —
 * bukan penguncian PLATFORM. Keduanya ortogonal; bila kelak keduanya ada, toggle
 * ini di-AND-kan DI BAWAH pemeriksaan paket, bukan sebaliknya. Tidak ada satu pun
 * logika paket yang boleh membaca atau menulis kolomnya.
 *
 * Granularitasnya sengaja SATU TUAS PER CLUSTER sidebar, bukan per menu: pesantren
 * memutuskan "kami tidak menjalankan tahfidz", bukan "kami mau Setoran tapi tidak
 * mau Ujian". Cluster Santri tidak punya case di sini — ia inti sistem.
 *
 * Enum ini sengaja TIDAK tahu apa-apa tentang App\Filament\Clusters\*. Pemetaan
 * komponen panel → modul hidup di App\Filament\Support\ModulKomponen, supaya enum
 * ini tetap bisa dipakai dari routes/web.php, controller wali, dan blade wali —
 * tempat-tempat yang tidak boleh menyeret kelas Filament.
 */
enum Modul: string
{
    case Akademik = 'akademik';
    case Tahfidz = 'tahfidz';
    case Presensi = 'presensi';
    case Kesantrian = 'kesantrian';
    case Keuangan = 'keuangan';
    case Rapor = 'rapor';

    /** Kolom boolean pasangannya di tabel modul_pengaturan. */
    public function kolom(): string
    {
        return $this->value.'_aktif';
    }

    public function label(): string
    {
        return match ($this) {
            self::Akademik => 'Akademik',
            self::Tahfidz => 'Tahfidz',
            self::Presensi => 'Presensi',
            self::Kesantrian => 'Kesantrian',
            self::Keuangan => 'Keuangan',
            self::Rapor => 'Rapor',
        };
    }

    /**
     * Apa persisnya yang lenyap saat modul ini dimatikan.
     *
     * Dipakai sebagai helperText di halaman Pengaturan Modul. Menyebut nama menunya
     * satu per satu, bukan kalimat umum "menonaktifkan modul": admin yang membalik
     * tuas ini sedang mengambil keputusan atas layar orang lain — ustadz dan wali
     * santri — dan ia berhak tahu apa yang akan mereka lihat besok pagi.
     */
    public function penjelasan(): string
    {
        return match ($this) {
            self::Akademik => 'Menyembunyikan menu Pelajaran, Nilai, dan Ekskul. Bagian Akademik di halaman Rapor dan kartu Ekskul di portal wali ikut hilang.',
            self::Tahfidz => 'Menyembunyikan menu Setoran dan Ujian tahfidz. Bagian Tahfidz di halaman Rapor serta capaian hafalan di portal wali ikut hilang.',
            self::Presensi => 'Menyembunyikan seluruh menu Presensi — Kehadiran, Rekap, Hari Libur, Pengajuan Izin, dan Pengaturan Presensi. Wali santri tidak lagi melihat kehadiran anaknya maupun bisa mengajukan izin.',
            self::Kesantrian => 'Menyembunyikan menu Mutabaah, Karakter, Kesehatan, dan Inventaris. Bagian Mutabaah DAN Karakter di halaman Rapor sama-sama hilang, karena keduanya milik modul ini.',
            self::Keuangan => 'Menyembunyikan menu Tagihan SPP dan Uang Saku Santri. Tab SPP dan Uang Saku di portal wali ikut hilang.',
            self::Rapor => 'Menyembunyikan halaman Rapor beserta cetak PDF-nya, di panel maupun portal wali. Modul lain tetap berjalan seperti biasa.',
        };
    }

    /**
     * Apakah modul ini menyala untuk pesantren yang bersangkutan.
     *
     * Tanpa konteks pesantren, jawabannya SELALU true. Itu yang membuat super_admin
     * (pesantren_id null) tidak pernah kehilangan menu apa pun, dan membuat perilaku
     * hari ini terjaga persis untuk siapa pun yang berada di luar satu tenant.
     *
     * ⚠️ Jangan pernah mengandalkan konteks tenant di jalur magic link — rute
     * wali.magic.report tidak memakai middleware tenant.resolve, jadi global scope
     * bisa tidak tersetel di sana (lihat SantriDetailPresenter). Di jalur itu,
     * oper $santri->pesantren_id secara eksplisit.
     */
    public function aktif(?int $pesantrenId = null): bool
    {
        $pesantrenId ??= Auth::user()?->pesantren_id;

        if ($pesantrenId === null) {
            return true;
        }

        return (bool) ModulPengaturan::untuk($pesantrenId)->{$this->kolom()};
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $modul) => [$modul->value => $modul->label()])
            ->all();
    }
}

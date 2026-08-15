<?php

namespace App\Observers;

use App\Models\Presensi;
use App\Support\Waktu;

/**
 * Mencatat perubahan SURUT pada presensi ke activity_logs.
 *
 * Presensi menghasilkan ±250.000 baris/tahun/tenant — mengauditnya seluruhnya
 * akan menenggelamkan tabel append-only itu dan membuat retensi §10.3 kehilangan
 * arti. Jadi pencatatan rutin tidak diaudit sama sekali; jejaknya cukup lewat
 * dicatat_oleh + sumber + updated_at di baris presensi itu sendiri.
 *
 * Yang dicatat hanya satu hal: status baris yang SUDAH ADA berubah, DAN tanggalnya
 * bukan hari ini. Koreksi di hari yang sama adalah pekerjaan normal (santri ternyata
 * datang terlambat); mengubah alpa bulan lalu adalah hal lain — wali membacanya di
 * portal, dan itulah satu-satunya kasus yang bisa berujung sengketa.
 *
 * Ini observer KEDUA di seluruh aplikasi yang mencatat event `updated` dengan nilai
 * lama-baru, setelah PesantrenObserver. Modul data santri lain (nilai, mutaba'ah,
 * tahfidz, SPP) masih tanpa jejak perubahan sama sekali — dicatat di §22 sebagai
 * batas yang diketahui, bukan kelalaian.
 */
class PresensiObserver
{
    public function updated(Presensi $presensi): void
    {
        if (! $presensi->wasChanged('status')) {
            return;
        }

        // Perbandingan memakai jam dinding WIB, bukan UTC: mengoreksi presensi
        // pukul 01.00 WIB masih "hari ini" bagi penggunanya.
        if ($presensi->tanggal->toDateString() === Waktu::hariIni()) {
            return;
        }

        $lama = $presensi->getOriginal('status');
        $baru = $presensi->status;

        ActivityLogger::log(
            'presensi.diubah',
            $presensi,
            [
                'status' => $lama instanceof \BackedEnum ? $lama->value : $lama,
                'tanggal' => $presensi->tanggal->toDateString(),
            ],
            [
                'status' => $baru?->value,
                'santri_id' => $presensi->santri_id,
            ],
        );
    }
}

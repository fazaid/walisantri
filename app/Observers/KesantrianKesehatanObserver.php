<?php

namespace App\Observers;

use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;

/**
 * Menurunkan status udzur mutaba'ah dari rekam kesehatan.
 *
 * PRD menjanjikan perilaku ini sejak lama ("Observer: Istirahat_Total/Rujukan_Luar
 * → auto-set status_udzur = Sakit di mutaba'ah harian", §3.2, dan §17 bahkan
 * mendaftarkannya sebagai tes wajib) — tapi observernya tidak pernah ada, jadi
 * selama ini ustadz harus mengisi udzur manual dan tidak ada yang menyadarinya
 * karena dokumennya menyatakan sebaliknya. Kelas ini menutup jarak itu.
 *
 * ⚠️ HANYA memperbarui baris mutaba'ah yang SUDAH ADA — tidak pernah membuat
 * baris baru. Alasannya aritmetik, bukan gaya: MutabaahScoreCalculator::persentaseRataRata()
 * memasukkan SETIAP baris ke penyebut tanpa memandang status_udzur. Membuat baris
 * kosong untuk hari sakit berarti menambah satu hari bernilai 0% ke rata-rata
 * santri — persentase amalannya turun justru karena ia sakit, dan angka itu
 * terbaca wali di portal serta tercetak di rapor.
 */
class KesantrianKesehatanObserver
{
    /** Status pemulihan yang berarti santri memang tidak bisa beraktivitas normal. */
    private const STATUS_UDZUR = ['Istirahat_Total', 'Rujukan_Luar'];

    public function created(KesantrianKesehatan $rekam): void
    {
        $this->terapkanUdzur($rekam);
    }

    public function updated(KesantrianKesehatan $rekam): void
    {
        // Hanya saat status pemulihannya benar-benar berubah — menyunting catatan
        // obat tidak boleh menimpa udzur yang sudah disetel ustadz secara sadar.
        if ($rekam->wasChanged('status_pemulihan')) {
            $this->terapkanUdzur($rekam);
        }
    }

    private function terapkanUdzur(KesantrianKesehatan $rekam): void
    {
        if (! in_array($rekam->status_pemulihan, self::STATUS_UDZUR, true)) {
            return;
        }

        if (! $rekam->tanggal_periksa) {
            return;
        }

        KesantrianMutabaah::where('santri_id', $rekam->santri_id)
            ->whereDate('tanggal', $rekam->tanggal_periksa)
            // 'Tidak' saja: kalau ustadz sudah menandai Haid/Izin_Pulang/Tugas_Pondok,
            // itu keterangan yang lebih spesifik dan tidak boleh ditimpa jadi 'Sakit'.
            ->where('status_udzur', 'Tidak')
            ->update(['status_udzur' => 'Sakit']);
    }
}

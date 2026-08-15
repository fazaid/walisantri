<?php

namespace App\Http\Controllers\Wali;

use App\Enums\StatusKehadiran;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\Presensi;
use App\Services\PresensiKalender;
use App\Services\PresensiRekap;
use App\Services\TrendBulanan;
use App\Support\Waktu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Presensi satu santri untuk wali — baca-saja, sebulan sekali lihat.
 *
 * Rekapnya TIDAK dihitung di sini: ia memakai `App\Services\PresensiRekap` yang
 * sama dengan halaman Rekap admin, ekspor Excel, dan rapor PDF. Menghitungnya
 * sendiri berarti "hari efektif" dan definisi "% kehadiran" punya empat versi,
 * dan wali yang membandingkan angka di portal dengan angka di rapor cetak adalah
 * orang pertama yang akan menemukan selisihnya (pelajaran v4.19).
 *
 * Hanya presensi HARIAN yang ditampilkan. Presensi per jam pelajaran (§3.2 Fase 6)
 * sengaja tidak diikutkan: penyebutnya berbeda — "hari efektif" tidak berlaku untuk
 * jam pelajaran, dan mencampurnya di satu daftar akan membuat wali membaca satu
 * hari yang sama beberapa kali dengan status berbeda tanpa penjelasan.
 */
class PresensiController extends Controller
{
    use ResolvesSantriMilikWali;

    public function show(int $santriId, Request $request)
    {
        $santri = $this->santriMilikWali($santriId);

        $bulanOptions = TrendBulanan::duaBelasBulanTerakhir();
        $bulan = $this->bulanTerpilih($request->query('bulan'), $bulanOptions);

        $awalBulan = Carbon::createFromFormat('Y-m', $bulan, Waktu::zona())->startOfMonth();
        $awal = $awalBulan->toDateString();
        $akhir = $awalBulan->copy()->endOfMonth()->toDateString();

        $rekap = PresensiRekap::untuk(
            $santri->pesantren_id,
            $awal,
            $akhir,
            santriId: $santri->id,
        );

        // Batas atas dipotong ke hari ini di dalam PresensiRekap — bulan berjalan
        // tidak boleh memasukkan sisa hari yang belum terjadi ke penyebut.
        [, $akhirEfektif] = $rekap->rentang();

        $ringkasan = $rekap->satuSantri();

        return view('wali.presensi.show', [
            'santri' => $santri,
            'bulan' => $bulan,
            'bulanOptions' => $bulanOptions,
            'ringkasan' => $ringkasan,
            'hitungan' => $this->hitunganStatus($rekap, $ringkasan),
            'harian' => $this->daftarHarian($santri->pesantren_id, $santri->id, $awal, $akhirEfektif),
        ]);
    }

    /**
     * Hitungan tiap status untuk kartu ringkasan.
     *
     * Status yang nol IKUT ditampilkan — "Alpa 0" adalah kabar baik yang justru
     * ingin dibaca wali, dan menyembunyikannya membuat kolom yang muncul-hilang
     * antar bulan sehingga posisi angkanya tidak pernah bisa dihafal.
     *
     * @return list<array{label: string, jumlah: int, warna: string}>
     */
    private function hitunganStatus(PresensiRekap $rekap, ?object $ringkasan): array
    {
        return array_map(fn (StatusKehadiran $status): array => [
            'label' => $status->label(),
            'jumlah' => (int) ($ringkasan?->{$rekap->kolomStatus($status)} ?? 0),
            'warna' => $status->color(),
        ], StatusKehadiran::cases());
    }

    /**
     * Daftar harian, dengan keterangan hari libur bila ada.
     *
     * Berangkat dari baris presensi yang ADA, bukan dari seluruh tanggal: hari
     * tanpa baris sudah terwakili angka "Tanpa Keterangan" di ringkasan, dan
     * mendaftarkannya satu per satu akan membuat halaman ini didominasi baris
     * kosong pada pesantren yang belum rutin mengabsen.
     *
     * @return list<array{tanggal: Carbon, status: StatusKehadiran, catatan: ?string, libur: ?string}>
     */
    private function daftarHarian(int $pesantrenId, int $santriId, string $awal, string $akhir): array
    {
        $kalender = PresensiKalender::untuk($pesantrenId);

        return Presensi::where('santri_id', $santriId)
            ->where('jam_ke', Presensi::HARIAN)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderByDesc('tanggal')
            ->get()
            ->map(fn (Presensi $baris): array => [
                'tanggal' => $baris->tanggal,
                'status' => $baris->status,
                'catatan' => $baris->catatan,
                'libur' => $kalender->keteranganLibur($baris->tanggal->toDateString()),
            ])
            ->all();
    }

    /**
     * Bulan dari query string, divalidasi terhadap daftar pilihan.
     *
     * Jatuh ke bulan berjalan bila tidak cocok — bukan 404. Wali lazim menyimpan
     * tautan lama, dan bulan yang sudah lewat dari jendela 12 bulan bukan
     * kesalahan yang pantas dijawab dengan halaman error.
     *
     * @param  array<int, array{key: string, label: string}>  $options
     */
    private function bulanTerpilih(?string $diminta, array $options): string
    {
        $sah = array_column($options, 'key');

        return in_array($diminta, $sah, true)
            ? $diminta
            : Waktu::sekarang()->format('Y-m');
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Pesantren;
use App\Models\PresensiHariLibur;
use App\Models\PresensiPengaturan;
use App\Services\PresensiKalender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Hari efektif" adalah penyebut persentase kehadiran yang nanti dibaca wali dan
 * tercetak di rapor, jadi salah hitung di sini menular ke mana-mana. Yang paling
 * rawan: penomoran hari — Carbon::dayOfWeek memakai 0 = Minggu, sedangkan
 * ISO-8601 memakai 1 = Senin … 7 = Minggu. Tertukar berarti seluruh perhitungan
 * bergeser satu hari tanpa satu pun error muncul.
 */
class PresensiKalenderTest extends TestCase
{
    use RefreshDatabase;

    private function pesantrenDenganLiburMingguan(array $hari): Pesantren
    {
        $pesantren = Pesantren::factory()->create();

        PresensiPengaturan::untuk($pesantren->id)->update(['hari_libur_mingguan' => $hari]);

        return $pesantren;
    }

    private function tambahLibur(Pesantren $pesantren, string $tanggal, string $keterangan): void
    {
        PresensiHariLibur::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $pesantren->id,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'tahun_ajaran' => '2026/2027',
        ]);
    }

    public function test_libur_mingguan_memakai_penomoran_carbon_bukan_iso(): void
    {
        // 0 = Minggu menurut Carbon::dayOfWeek. Kalau kode diam-diam memakai
        // ISO-8601, angka 0 tidak akan cocok dengan hari mana pun dan Minggu
        // justru dihitung sebagai hari sekolah.
        $pesantren = $this->pesantrenDenganLiburMingguan([0]);
        $kalender = PresensiKalender::untuk($pesantren->id);

        // 2026-09-13 adalah hari Minggu.
        $this->assertTrue($kalender->adalahLibur('2026-09-13'));
        $this->assertFalse($kalender->adalahLibur('2026-09-14')); // Senin
    }

    public function test_libur_mingguan_jumat_untuk_pesantren_yang_liburnya_bukan_minggu(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([5]); // 5 = Jumat
        $kalender = PresensiKalender::untuk($pesantren->id);

        $this->assertTrue($kalender->adalahLibur('2026-09-11'));  // Jumat
        $this->assertFalse($kalender->adalahLibur('2026-09-13')); // Minggu
    }

    public function test_libur_kalender_terdeteksi_dan_keterangannya_terbaca(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([]);
        $this->tambahLibur($pesantren, '2026-09-15', 'Maulid Nabi');

        $kalender = PresensiKalender::untuk($pesantren->id);

        $this->assertTrue($kalender->adalahLibur('2026-09-15'));
        $this->assertSame('Maulid Nabi', $kalender->keteranganLibur('2026-09-15'));
        $this->assertNull($kalender->keteranganLibur('2026-09-16'));
    }

    public function test_libur_kalender_didahulukan_saat_bertabrakan_dengan_libur_mingguan(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([0]);
        $this->tambahLibur($pesantren, '2026-09-13', 'Maulid Nabi'); // Minggu

        // "Maulid Nabi" lebih berguna bagi pengguna daripada "Libur Minggu".
        $this->assertSame('Maulid Nabi', PresensiKalender::untuk($pesantren->id)->keteranganLibur('2026-09-13'));
    }

    public function test_keterangan_libur_mingguan_menyebut_nama_harinya(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([0]);

        $this->assertStringContainsString(
            'Libur',
            PresensiKalender::untuk($pesantren->id)->keteranganLibur('2026-09-13')
        );
    }

    public function test_hari_efektif_mengurangi_libur_mingguan_dan_kalender(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([0]); // Minggu libur
        $this->tambahLibur($pesantren, '2026-09-09', 'Maulid Nabi'); // Rabu

        // 7–13 September 2026 = Senin s.d. Minggu (7 hari kalender).
        // Dikurangi 1 Minggu + 1 libur kalender = 5 hari efektif.
        $this->assertSame(5, PresensiKalender::untuk($pesantren->id)->hariEfektif('2026-09-07', '2026-09-13'));
    }

    public function test_hari_efektif_tanpa_libur_sama_dengan_jumlah_hari_kalender(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([]);

        $this->assertSame(7, PresensiKalender::untuk($pesantren->id)->hariEfektif('2026-09-07', '2026-09-13'));
    }

    public function test_rentang_sehari_inklusif_di_kedua_ujung(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([]);

        // Kasus tepi klasik: rentang dengan awal == akhir harus menghasilkan 1,
        // bukan 0 (diff) dan bukan 2 (salah hitung inklusif).
        $this->assertSame(1, PresensiKalender::untuk($pesantren->id)->hariEfektif('2026-09-14', '2026-09-14'));
    }

    public function test_rentang_terbalik_menghasilkan_kosong(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([]);

        $this->assertSame(0, PresensiKalender::untuk($pesantren->id)->hariEfektif('2026-09-14', '2026-09-07'));
    }

    public function test_libur_pesantren_lain_tidak_ikut_terhitung(): void
    {
        $pesantrenA = $this->pesantrenDenganLiburMingguan([]);
        $pesantrenB = $this->pesantrenDenganLiburMingguan([]);

        $this->tambahLibur($pesantrenB, '2026-09-09', 'Libur Pesantren B');

        $this->assertFalse(PresensiKalender::untuk($pesantrenA->id)->adalahLibur('2026-09-09'));
        $this->assertTrue(PresensiKalender::untuk($pesantrenB->id)->adalahLibur('2026-09-09'));
    }

    public function test_tanggal_efektif_mengembalikan_daftar_tanggalnya(): void
    {
        $pesantren = $this->pesantrenDenganLiburMingguan([0]);
        $this->tambahLibur($pesantren, '2026-09-09', 'Maulid Nabi');

        $efektif = PresensiKalender::untuk($pesantren->id)->tanggalEfektif('2026-09-07', '2026-09-13');

        $this->assertSame(
            ['2026-09-07', '2026-09-08', '2026-09-10', '2026-09-11', '2026-09-12'],
            $efektif
        );
    }
}

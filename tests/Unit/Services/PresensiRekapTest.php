<?php

namespace Tests\Unit\Services;

use App\Enums\StatusKehadiran;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiHariLibur;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Services\PresensiRekap;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PresensiRekapTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);

        // Tanpa libur mingguan, supaya hari efektif = jumlah hari kalender dan
        // aritmetika tiap kasus bisa dibaca langsung dari tanggalnya.
        PresensiPengaturan::untuk($this->pesantren->id)->update(['hari_libur_mingguan' => []]);
    }

    private function santri(string $nama = 'Ahmad'): Santri
    {
        return Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'nama_lengkap' => $nama,
        ]);
    }

    private function catat(Santri $santri, string $tanggal, StatusKehadiran $status): void
    {
        Presensi::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'kelas_id' => $santri->kelas_id,
            'tanggal' => $tanggal,
            'jam_ke' => Presensi::HARIAN,
            'status' => $status,
        ]);
    }

    private function rekap(string $awal, string $akhir): PresensiRekap
    {
        return PresensiRekap::untuk($this->pesantren->id, $awal, $akhir);
    }

    public function test_santri_yang_belum_pernah_diabsen_tetap_muncul(): void
    {
        $this->santri('Belum Pernah Diabsen');

        $rows = $this->rekap('2026-08-03', '2026-08-07')->perSantri();

        // Justru merekalah yang paling perlu terlihat — kalau rekap berangkat dari
        // tabel presensi alih-alih dari santri, mereka hilang sama sekali.
        $this->assertCount(1, $rows);
        $this->assertSame(5, $rows->first()->tanpa_keterangan);
        $this->assertSame(0, $rows->first()->persen_kehadiran);
    }

    public function test_hitungan_per_status_benar(): void
    {
        $santri = $this->santri();

        $this->catat($santri, '2026-08-03', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-04', StatusKehadiran::Sakit);
        $this->catat($santri, '2026-08-05', StatusKehadiran::Alpa);
        $this->catat($santri, '2026-08-06', StatusKehadiran::Terlambat);
        $this->catat($santri, '2026-08-07', StatusKehadiran::Dispensasi);

        $baris = $this->rekap('2026-08-03', '2026-08-07')->perSantri()->first();

        $this->assertSame(1, (int) $baris->jml_hadir);
        $this->assertSame(1, (int) $baris->jml_sakit);
        $this->assertSame(1, (int) $baris->jml_alpa);
        $this->assertSame(1, (int) $baris->jml_terlambat);
        $this->assertSame(1, (int) $baris->jml_dispensasi);
        $this->assertSame(0, $baris->tanpa_keterangan);
    }

    public function test_persen_kehadiran_memakai_definisi_hadir_efektif(): void
    {
        $santri = $this->santri();

        // Hadir + Terlambat + Dispensasi dihitung hadir (StatusKehadiran::hadirEfektif),
        // Sakit dan Alpa tidak. 3 dari 5 hari = 60%.
        $this->catat($santri, '2026-08-03', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-04', StatusKehadiran::Terlambat);
        $this->catat($santri, '2026-08-05', StatusKehadiran::Dispensasi);
        $this->catat($santri, '2026-08-06', StatusKehadiran::Sakit);
        $this->catat($santri, '2026-08-07', StatusKehadiran::Alpa);

        $baris = $this->rekap('2026-08-03', '2026-08-07')->perSantri()->first();

        $this->assertSame(3, $baris->hadir_efektif);
        $this->assertSame(60, $baris->persen_kehadiran);
    }

    public function test_hari_libur_dikeluarkan_dari_penyebut(): void
    {
        $santri = $this->santri();

        PresensiHariLibur::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'tanggal' => '2026-08-05',
            'keterangan' => 'Maulid Nabi',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->catat($santri, '2026-08-03', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-04', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-06', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-07', StatusKehadiran::Hadir);

        $rekap = $this->rekap('2026-08-03', '2026-08-07');

        // 5 hari kalender − 1 libur = 4 hari efektif, semuanya hadir = 100%.
        $this->assertSame(4, $rekap->hariEfektif());
        $this->assertSame(100, $rekap->perSantri()->first()->persen_kehadiran);
        $this->assertSame(0, $rekap->perSantri()->first()->tanpa_keterangan);
    }

    public function test_rentang_tidak_pernah_melewati_hari_ini(): void
    {
        $this->santri();

        // Periode semester berakhir jauh di depan. Tanpa clamp, seluruh sisa tahun
        // masuk penyebut dan persentase kehadiran setiap santri anjlok tanpa ada
        // yang salah.
        $rekap = PresensiRekap::untuk(
            $this->pesantren->id,
            Waktu::sekarang()->subDays(2)->toDateString(),
            Waktu::sekarang()->addYear()->toDateString(),
        );

        [, $akhir] = $rekap->rentang();

        $this->assertSame(Waktu::hariIni(), $akhir);
        $this->assertSame(3, $rekap->hariEfektif());
    }

    public function test_santri_terhapus_tidak_ikut_terhitung(): void
    {
        $tetap = $this->santri('Masih Ada');
        $keluar = $this->santri('Sudah Keluar');

        $this->catat($tetap, '2026-08-03', StatusKehadiran::Hadir);
        $this->catat($keluar, '2026-08-03', StatusKehadiran::Hadir);

        $keluar->delete();

        $rows = $this->rekap('2026-08-03', '2026-08-03')->perSantri();

        $this->assertCount(1, $rows);
        $this->assertSame('Masih Ada', $rows->first()->nama_lengkap);
    }

    public function test_alpa_beruntun_terdeteksi(): void
    {
        $santri = $this->santri();

        foreach (['2026-08-03', '2026-08-04', '2026-08-05'] as $tanggal) {
            $this->catat($santri, $tanggal, StatusKehadiran::Alpa);
        }

        $perhatian = $this->rekap('2026-08-03', '2026-08-07')->alpaBeruntun(3);

        $this->assertCount(1, $perhatian);
        $this->assertSame(3, $perhatian->first()->beruntun);
    }

    public function test_alpa_terpencar_tidak_dianggap_beruntun(): void
    {
        $santri = $this->santri();

        // Tiga alpa, tapi diselingi hadir — bukan pola yang perlu diwaspadai.
        $this->catat($santri, '2026-08-03', StatusKehadiran::Alpa);
        $this->catat($santri, '2026-08-04', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-05', StatusKehadiran::Alpa);
        $this->catat($santri, '2026-08-06', StatusKehadiran::Hadir);
        $this->catat($santri, '2026-08-07', StatusKehadiran::Alpa);

        $this->assertCount(0, $this->rekap('2026-08-03', '2026-08-07')->alpaBeruntun(3));
    }

    public function test_alpa_beruntun_melompati_hari_libur(): void
    {
        $santri = $this->santri();

        // 2026-08-05 libur. Alpa 3, 4, lalu 6 adalah TIGA hari efektif berturut-turut
        // meski di kalender terpisah. Menghitungnya atas hari kalender akan memutus
        // rangkaian setiap akhir pekan dan membuat panel ini nyaris tak pernah menyala.
        PresensiHariLibur::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'tanggal' => '2026-08-05',
            'keterangan' => 'Libur',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->catat($santri, '2026-08-03', StatusKehadiran::Alpa);
        $this->catat($santri, '2026-08-04', StatusKehadiran::Alpa);
        $this->catat($santri, '2026-08-06', StatusKehadiran::Alpa);

        $perhatian = $this->rekap('2026-08-03', '2026-08-07')->alpaBeruntun(3);

        $this->assertCount(1, $perhatian);
        $this->assertSame(3, $perhatian->first()->beruntun);
    }

    public function test_rekap_bisa_disaring_per_kelas(): void
    {
        $kelasLain = Kelas::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kelas' => 'Kelas Lain',
        ]);

        $this->santri('Di Kelas Ini');
        Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelasLain->id,
            'nama_lengkap' => 'Di Kelas Lain',
        ]);

        $rows = PresensiRekap::untuk($this->pesantren->id, '2026-08-03', '2026-08-03', $this->kelas->id)
            ->perSantri();

        $this->assertCount(1, $rows);
        $this->assertSame('Di Kelas Ini', $rows->first()->nama_lengkap);
    }

    public function test_rekap_pesantren_lain_tidak_bocor(): void
    {
        $this->santri('Punya Kita');

        $lain = Pesantren::factory()->create();
        $kelasLain = Kelas::factory()->create(['pesantren_id' => $lain->id]);
        Santri::factory()->create([
            'pesantren_id' => $lain->id,
            'kelas_id' => $kelasLain->id,
            'nama_lengkap' => 'Punya Tetangga',
        ]);

        $rows = $this->rekap('2026-08-03', '2026-08-03')->perSantri();

        $this->assertCount(1, $rows);
        $this->assertSame('Punya Kita', $rows->first()->nama_lengkap);
    }

    public function test_ringkasan_menjumlahkan_seluruh_santri(): void
    {
        $a = $this->santri('A');
        $this->santri('B');

        $this->catat($a, '2026-08-03', StatusKehadiran::Hadir);

        $ringkasan = $this->rekap('2026-08-03', '2026-08-04')->ringkasan();

        $this->assertSame(2, $ringkasan->jumlah_santri);
        $this->assertSame(2, $ringkasan->hari_efektif);
        // A: 1 dari 2 hari tanpa keterangan. B: 2 dari 2. Total 3.
        $this->assertSame(3, $ringkasan->tanpa_keterangan);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit\Services;

use App\Enums\JenisIzin;
use App\Enums\StatusKehadiran;
use App\Enums\StatusPengajuanIzin;
use App\Enums\SumberPresensi;
use App\Models\Kelas;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiHariLibur;
use App\Models\PresensiIzin;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Services\MutabaahScoreCalculator;
use App\Services\PresensiIzinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiIzinServiceTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private Santri $santri;

    private User $admin;

    private PresensiIzinService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->admin = User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);

        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        PresensiPengaturan::untuk($this->pesantren->id)->update(['hari_libur_mingguan' => []]);

        $this->service = app(PresensiIzinService::class);
    }

    private function izin(
        JenisIzin $jenis = JenisIzin::Sakit,
        string $mulai = '2026-08-03',
        string $selesai = '2026-08-05',
    ): PresensiIzin {
        return PresensiIzin::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'jenis' => $jenis,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'alasan' => 'Demam tinggi',
            'status' => StatusPengajuanIzin::Diajukan,
        ]);
    }

    public function test_persetujuan_mengisi_presensi_tiap_tanggal_dalam_rentang(): void
    {
        $izin = $this->izin();

        $this->service->setujui($izin, $this->admin);

        $this->assertSame(StatusPengajuanIzin::Disetujui, $izin->fresh()->status);
        $this->assertSame(3, Presensi::withoutGlobalScope('pesantren')->count());

        $baris = Presensi::withoutGlobalScope('pesantren')->first();
        $this->assertSame(StatusKehadiran::Sakit, $baris->status);
        $this->assertSame(SumberPresensi::Izin, $baris->sumber);
        $this->assertSame($izin->id, $baris->presensi_izin_id);
        $this->assertSame($this->santri->kelas_id, $baris->kelas_id);
    }

    public function test_hari_libur_dilewati_saat_persetujuan(): void
    {
        PresensiHariLibur::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'tanggal' => '2026-08-04',
            'keterangan' => 'Maulid Nabi',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->service->setujui($this->izin(), $this->admin);

        // Mencatat "Sakit" pada hari yang tidak menuntut kehadiran akan mengotori
        // penyebut rekap dan membuat santri tampak absen tanpa sebab.
        $this->assertSame(2, Presensi::withoutGlobalScope('pesantren')->count());
        $this->assertSame(
            0,
            Presensi::withoutGlobalScope('pesantren')->whereDate('tanggal', '2026-08-04')->count()
        );
    }

    public function test_pemetaan_jenis_izin_ke_status_kehadiran(): void
    {
        $this->assertSame(StatusKehadiran::Sakit, JenisIzin::Sakit->keStatusKehadiran());
        $this->assertSame(StatusKehadiran::Izin, JenisIzin::Izin->keStatusKehadiran());
        $this->assertSame(StatusKehadiran::Pulang, JenisIzin::Pulang->keStatusKehadiran());
        $this->assertSame(StatusKehadiran::Dispensasi, JenisIzin::Dispensasi->keStatusKehadiran());
    }

    public function test_penolakan_tidak_menulis_presensi(): void
    {
        $izin = $this->izin();

        $this->service->tolak($izin, $this->admin, 'Alasan kurang jelas');

        $this->assertSame(StatusPengajuanIzin::Ditolak, $izin->fresh()->status);
        $this->assertSame('Alasan kurang jelas', $izin->fresh()->catatan_petugas);
        $this->assertSame(0, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_pembatalan_menghapus_baris_presensi_turunannya(): void
    {
        $izin = $this->izin();
        $this->service->setujui($izin, $this->admin);
        $this->assertSame(3, Presensi::withoutGlobalScope('pesantren')->count());

        $this->service->batalkan($izin, $this->admin, 'Salah input');

        $this->assertSame(StatusPengajuanIzin::Dibatalkan, $izin->fresh()->status);
        $this->assertSame(0, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_pembatalan_tidak_menghapus_baris_yang_sudah_disunting_manual(): void
    {
        $izin = $this->izin();
        $this->service->setujui($izin, $this->admin);

        // Ustadz mengoreksi satu hari: ternyata santri masuk. Barisnya berpindah
        // ke sumber 'manual'.
        Presensi::withoutGlobalScope('pesantren')
            ->whereDate('tanggal', '2026-08-04')
            ->update([
                'status' => StatusKehadiran::Hadir->value,
                'sumber' => SumberPresensi::Manual->value,
            ]);

        $this->service->batalkan($izin, $this->admin);

        // Koreksi manusia tidak boleh dihapus oleh pembatalan otomatis — yang
        // membatalkan izin tidak sedang menyatakan catatan manual itu salah.
        $tersisa = Presensi::withoutGlobalScope('pesantren')->get();
        $this->assertCount(1, $tersisa);
        $this->assertSame(StatusKehadiran::Hadir, $tersisa->first()->status);
    }

    public function test_persetujuan_menyelaraskan_udzur_mutabaah_yang_sudah_ada(): void
    {
        KesantrianAmalMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'kode' => 'is_dhuha', 'label' => 'Dhuha', 'tipe' => 'boolean',
            'satuan' => 'hari', 'bobot' => 7, 'urutan' => 1, 'aktif' => true,
        ]);
        MutabaahScoreCalculator::clearCache();

        $mutabaah = KesantrianMutabaah::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => '2026-08-04',
            'amalan' => ['is_dhuha' => true],
            'status_udzur' => 'Tidak',
        ]);

        $this->service->setujui($this->izin(), $this->admin);

        $this->assertSame('Sakit', $mutabaah->fresh()->status_udzur);
    }

    public function test_persetujuan_tidak_membuat_baris_mutabaah_baru(): void
    {
        $this->service->setujui($this->izin(), $this->admin);

        // Kalau service memakai updateOrCreate, tiga baris kosong akan lahir dan
        // masuk penyebut MutabaahScoreCalculator::persentaseRataRata() sebagai hari
        // bernilai 0% — persentase amalan santri turun justru karena ia sakit, dan
        // angka itu dibaca wali serta tercetak di rapor.
        $this->assertSame(0, KesantrianMutabaah::withoutGlobalScope('pesantren')->count());
    }

    public function test_udzur_yang_lebih_spesifik_tidak_ditimpa(): void
    {
        $mutabaah = KesantrianMutabaah::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => '2026-08-04',
            'amalan' => [],
            'status_udzur' => 'Haid',
        ]);

        $this->service->setujui($this->izin(), $this->admin);

        $this->assertSame('Haid', $mutabaah->fresh()->status_udzur);
    }

    public function test_izin_pulang_dan_izin_biasa_sama_sama_jadi_izin_pulang_di_mutabaah(): void
    {
        $this->assertSame('Izin_Pulang', JenisIzin::Izin->keStatusUdzur());
        $this->assertSame('Izin_Pulang', JenisIzin::Pulang->keStatusUdzur());
        $this->assertSame('Tugas_Pondok', JenisIzin::Dispensasi->keStatusUdzur());
    }

    public function test_deteksi_rentang_beririsan(): void
    {
        $ada = $this->izin(mulai: '2026-08-03', selesai: '2026-08-07');

        // Beririsan sebagian di ujung awal.
        $this->assertTrue(PresensiIzin::beririsan($this->santri->id, '2026-08-01', '2026-08-04')->exists());
        // Termuat seluruhnya di dalam rentang yang ada.
        $this->assertTrue(PresensiIzin::beririsan($this->santri->id, '2026-08-04', '2026-08-05')->exists());
        // Membungkus rentang yang ada.
        $this->assertTrue(PresensiIzin::beririsan($this->santri->id, '2026-08-01', '2026-08-10')->exists());
        // Sama sekali di luar.
        $this->assertFalse(PresensiIzin::beririsan($this->santri->id, '2026-08-08', '2026-08-10')->exists());
        // Dirinya sendiri saat disunting tidak dihitung bentrok.
        $this->assertFalse(PresensiIzin::beririsan($this->santri->id, '2026-08-03', '2026-08-07', $ada->id)->exists());
    }

    public function test_izin_yang_ditolak_tidak_menghalangi_pengajuan_baru(): void
    {
        $ditolak = $this->izin(mulai: '2026-08-03', selesai: '2026-08-07');
        $this->service->tolak($ditolak, $this->admin);

        // Yang ditolak tidak pernah menulis presensi, jadi tidak ada yang bisa
        // ditimpa — menghalangi pengajuan ulang hanya akan menyulitkan wali.
        $this->assertFalse(PresensiIzin::beririsan($this->santri->id, '2026-08-03', '2026-08-07')->exists());
    }

    public function test_persetujuan_menimpa_presensi_manual_yang_sudah_ada(): void
    {
        Presensi::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => '2026-08-04',
            'jam_ke' => Presensi::HARIAN,
            'status' => StatusKehadiran::Alpa,
            'sumber' => SumberPresensi::Manual,
        ]);

        $this->service->setujui($this->izin(), $this->admin);

        // Persetujuan adalah keputusan yang lebih baru: santri yang tadinya dikira
        // bolos ternyata memang berhalangan.
        $baris = Presensi::withoutGlobalScope('pesantren')->whereDate('tanggal', '2026-08-04')->first();
        $this->assertSame(StatusKehadiran::Sakit, $baris->status);
        $this->assertSame(3, Presensi::withoutGlobalScope('pesantren')->count());
    }
}

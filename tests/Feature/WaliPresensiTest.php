<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiHariLibur;
use App\Models\Santri;
use App\Models\User;
use App\Services\PresensiKalender;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman presensi portal wali (v4.40) — baca-saja, satu santri, satu bulan.
 *
 * Yang paling penting dijaga di sini bukan tampilannya, melainkan bahwa angkanya
 * berasal dari `PresensiRekap` yang sama dengan panel admin. Wali yang
 * membandingkan persentase di portal dengan persentase di rapor cetak adalah orang
 * pertama yang akan menemukan selisih bila keduanya menghitung sendiri-sendiri
 * (pelajaran v4.19).
 */
class WaliPresensiTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $wali;

    private Santri $santri;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->wali = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);
        $this->kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);
        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'nama_lengkap' => 'Anak Wali Pertama',
            'status_aktif' => true,
        ]);
    }

    private function catat(string $tanggal, StatusKehadiran $status, ?string $catatan = null): Presensi
    {
        return Presensi::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => $tanggal,
            'jam_ke' => Presensi::HARIAN,
            'kelas_id' => $this->kelas->id,
            'status' => $status,
            'catatan' => $catatan,
        ]);
    }

    public function test_wali_bisa_membuka_presensi_anaknya(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Hadir);

        $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk()
            ->assertSee('Anak Wali Pertama')
            ->assertSee('Catatan Harian');
    }

    public function test_wali_tidak_bisa_membuka_presensi_anak_keluarga_lain(): void
    {
        $waliLain = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);
        $tetangga = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $waliLain->id,
            'kelas_id' => $this->kelas->id,
        ]);

        // Global scope Multitenantable hanya menyaring pesantren_id, BUKAN
        // wali_santri_id — persis bug §8 #1 yang pernah terjadi. Kepemilikan
        // ditegakkan ResolvesSantriMilikWali, bukan oleh scope.
        $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $tetangga->id))
            ->assertNotFound();
    }

    public function test_status_hadir_terlambat_dan_dispensasi_sama_sama_dihitung_hadir(): void
    {
        $bulan = Waktu::sekarang()->startOfMonth();

        $this->catat($bulan->copy()->toDateString(), StatusKehadiran::Hadir);
        $this->catat($bulan->copy()->addDay()->toDateString(), StatusKehadiran::Terlambat);
        $this->catat($bulan->copy()->addDays(2)->toDateString(), StatusKehadiran::Dispensasi);
        $this->catat($bulan->copy()->addDays(3)->toDateString(), StatusKehadiran::Alpa);

        $response = $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk();

        // Definisinya hidup di StatusKehadiran::hadirEfektif(), dan halaman ini
        // memakainya lewat PresensiRekap — bukan versi sendiri.
        $ringkasan = $response->viewData('ringkasan');
        $this->assertSame(3, (int) $ringkasan->hadir_efektif);
    }

    public function test_hari_libur_mengurangi_penyebut_tepat_satu_hari(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Hadir);

        $sebelum = $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk()
            ->viewData('ringkasan')->hari_efektif;

        // Tanggalnya dipilih dari daftar hari efektif yang sebenarnya, bukan
        // ditebak: menaruh libur di hari yang KEBETULAN sudah Minggu tidak akan
        // mengubah apa pun, dan tesnya akan gagal karena alasan yang salah.
        $efektif = PresensiKalender::untuk($this->pesantren->id)->tanggalEfektif(
            Waktu::sekarang()->startOfMonth()->toDateString(),
            Waktu::hariIni(),
        );

        PresensiHariLibur::create([
            'pesantren_id' => $this->pesantren->id,
            'tanggal' => $efektif[0],
            'keterangan' => 'Maulid Nabi',
            'tahun_ajaran' => '2026/2027',
        ]);

        $sesudah = $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk()
            ->viewData('ringkasan')->hari_efektif;

        // Persis satu. Kalau libur tidak dikurangi dari penyebut, persentase
        // kehadiran setiap santri turun tiap kali pondok libur.
        $this->assertSame($sebelum - 1, $sesudah);
    }

    public function test_bulan_dari_query_string_dihormati(): void
    {
        $bulanLalu = Waktu::sekarang()->subMonth();

        $this->catat($bulanLalu->copy()->startOfMonth()->toDateString(), StatusKehadiran::Sakit, 'Demam');

        $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', [$this->santri->id, 'bulan' => $bulanLalu->format('Y-m')]))
            ->assertOk()
            ->assertSee('Demam');
    }

    /**
     * Bulan ngawur JATUH ke bulan berjalan, bukan 404.
     *
     * Wali lazim menyimpan tautan lama, dan bulan di luar jendela 12 bulan bukan
     * kesalahan yang pantas dijawab halaman error.
     */
    public function test_bulan_di_luar_jendela_jatuh_ke_bulan_berjalan(): void
    {
        $response = $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', [$this->santri->id, 'bulan' => '1999-01']))
            ->assertOk();

        $this->assertSame(Waktu::sekarang()->format('Y-m'), $response->viewData('bulan'));
    }

    /**
     * Bulan berjalan tidak boleh memasukkan sisa hari yang belum terjadi.
     *
     * Tanpa pemotongan ini, membuka halaman tanggal 3 akan menghitung seluruh
     * bulan sebagai penyebut dan persentase kehadiran setiap santri anjlok tanpa
     * ada yang salah.
     */
    public function test_hari_yang_belum_terjadi_tidak_masuk_penyebut(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Hadir);

        $ringkasan = $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk()
            ->viewData('ringkasan');

        // Penyebutnya tidak boleh melampaui jumlah hari yang SUDAH berjalan.
        $this->assertLessThanOrEqual(Waktu::sekarang()->day, $ringkasan->hari_efektif);
    }

    public function test_presensi_jam_pelajaran_tidak_ikut_di_daftar_harian(): void
    {
        $hariIni = Waktu::hariIni();

        $this->catat($hariIni, StatusKehadiran::Hadir);

        Presensi::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => $hariIni,
            'jam_ke' => 3,
            'kelas_id' => $this->kelas->id,
            'status' => StatusKehadiran::Alpa,
            'catatan' => 'Bolos jam ketiga',
        ]);

        // Penyebut "hari efektif" tidak berlaku untuk jam pelajaran; mencampurnya
        // membuat wali membaca satu hari yang sama dua kali dengan status berbeda.
        $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk()
            ->assertDontSee('Bolos jam ketiga');
    }

    public function test_tautan_presensi_muncul_di_detail_santri(): void
    {
        $this->actingAs($this->wali)
            ->get(route('wali.santri.show', $this->santri->id))
            ->assertOk()
            ->assertSee(route('wali.santri.presensi', $this->santri->id));
    }

    public function test_beranda_memberi_tahu_anak_yang_hari_ini_tidak_hadir(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Alpa);

        $this->actingAs($this->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('Kehadiran Hari Ini')
            ->assertSee('Anak Wali Pertama');
    }

    /**
     * Terlambat TIDAK memicu banner.
     *
     * Ia dihitung hadir (StatusKehadiran::hadirEfektif()), dan memakai definisi
     * berbeda di beranda berarti wali membaca "tidak hadir" lalu melihat "100%
     * hadir" di halaman presensi anak yang sama.
     */
    public function test_beranda_tidak_memberi_peringatan_untuk_status_yang_dihitung_hadir(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Terlambat);

        $this->actingAs($this->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertDontSee('Kehadiran Hari Ini');
    }

    /**
     * Hari tanpa catatan TIDAK pernah dianggap ketidakhadiran.
     *
     * Sistem ini tidak menandai Alpa otomatis (§11); menebaknya di beranda berarti
     * mengirim kabar buruk ke orang tua hanya karena ustadznya belum sempat mengisi.
     */
    public function test_beranda_diam_saat_presensi_hari_ini_belum_diisi(): void
    {
        $this->catat(Waktu::sekarang()->subDay()->toDateString(), StatusKehadiran::Alpa);

        $this->actingAs($this->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertDontSee('Kehadiran Hari Ini');
    }

    public function test_beranda_tidak_membocorkan_kehadiran_anak_keluarga_lain(): void
    {
        $waliLain = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);
        $tetangga = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $waliLain->id,
            'kelas_id' => $this->kelas->id,
            'nama_lengkap' => 'Anak Keluarga Lain',
        ]);

        Presensi::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $tetangga->id,
            'tanggal' => Waktu::hariIni(),
            'jam_ke' => Presensi::HARIAN,
            'status' => StatusKehadiran::Alpa,
        ]);

        $this->actingAs($this->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertDontSee('Anak Keluarga Lain');
    }

    public function test_sesi_magic_link_boleh_membuka_presensi_santrinya(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Hadir);

        $this->get('/report/'.$this->santri->uuid)->assertOk();

        // Kartunya tampil di report, jadi menekannya tidak boleh memantulkan wali
        // kembali ke report tanpa penjelasan.
        $this->get(route('wali.santri.presensi', $this->santri->id))->assertOk();
    }

    public function test_sesi_magic_link_tidak_bisa_membuka_presensi_santri_lain(): void
    {
        $waliLain = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);
        $tetangga = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $waliLain->id,
            'kelas_id' => $this->kelas->id,
        ]);

        $this->get('/report/'.$this->santri->uuid)->assertOk();

        $this->get(route('wali.santri.presensi', $tetangga->id))
            ->assertRedirect(route('wali.magic.report', $this->santri->uuid));
    }

    public function test_hitungan_status_selalu_lengkap_meski_nol(): void
    {
        $this->catat(Waktu::hariIni(), StatusKehadiran::Hadir);

        $hitungan = $this->actingAs($this->wali)
            ->get(route('wali.santri.presensi', $this->santri->id))
            ->assertOk()
            ->viewData('hitungan');

        // Tujuh status, termasuk yang nol: "Alpa 0" adalah kabar baik yang justru
        // ingin dibaca wali, dan kolom yang muncul-hilang antar bulan membuat
        // posisi angkanya tidak pernah bisa dihafal.
        $this->assertCount(count(StatusKehadiran::cases()), $hitungan);
        $this->assertSame(1, collect($hitungan)->firstWhere('label', 'Hadir')['jumlah']);
        $this->assertSame(0, collect($hitungan)->firstWhere('label', 'Alpa')['jumlah']);
    }
}

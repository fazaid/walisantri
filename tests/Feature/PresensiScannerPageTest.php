<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Filament\Pages\PresensiScannerPage;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Support\KodePresensi;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PresensiScannerPageTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $admin;

    private Kelas $kelas;

    private Santri $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->admin = User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
        $this->kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);

        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'nama_lengkap' => 'Ahmad Fauzi',
        ]);

        PresensiPengaturan::untuk($this->pesantren->id)->update([
            'jam_masuk' => '07:00:00',
            'toleransi_terlambat_menit' => 15,
            'hari_libur_mingguan' => [],
        ]);
    }

    private function scan(string $masukan, ?User $sebagai = null)
    {
        return Livewire::actingAs($sebagai ?? $this->admin)
            ->test(PresensiScannerPage::class)
            ->set('kode', $masukan)
            ->call('scan');
    }

    /** Bekukan jam ke waktu WIB tertentu pada hari ini. */
    private function bekukanJam(string $jam): void
    {
        Carbon::setTestNow(
            Carbon::parse(Waktu::hariIni().' '.$jam, Waktu::zona())->utc()
        );
    }

    public function test_scan_sebelum_batas_tercatat_hadir(): void
    {
        $this->bekukanJam('06:45');

        $this->scan(KodePresensi::payload($this->santri->kode_presensi))
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Hadir.');

        $baris = Presensi::withoutGlobalScope('pesantren')->first();

        $this->assertSame(StatusKehadiran::Hadir, $baris->status);
        $this->assertSame(SumberPresensi::Qr, $baris->sumber);
        $this->assertSame($this->kelas->id, $baris->kelas_id);
        $this->assertNull($baris->menit_terlambat);
    }

    public function test_scan_setelah_batas_toleransi_tercatat_terlambat(): void
    {
        // Jam masuk 07:00 + toleransi 15 menit = batas 07:15. Pukul 07:30 berarti
        // terlambat 15 menit.
        $this->bekukanJam('07:30');

        $this->scan(KodePresensi::payload($this->santri->kode_presensi))
            ->assertSee('Terlambat 15 menit.');

        $baris = Presensi::withoutGlobalScope('pesantren')->first();

        $this->assertSame(StatusKehadiran::Terlambat, $baris->status);
        $this->assertSame(15, $baris->menit_terlambat);
    }

    public function test_tepat_di_batas_toleransi_masih_hadir(): void
    {
        // Kasus tepi: pukul 07:15 persis adalah batas terakhir, bukan sudah lewat.
        $this->bekukanJam('07:15');

        $this->scan(KodePresensi::payload($this->santri->kode_presensi));

        $this->assertSame(
            StatusKehadiran::Hadir,
            Presensi::withoutGlobalScope('pesantren')->first()->status
        );
    }

    public function test_scan_kedua_tidak_menimpa_jam_pertama(): void
    {
        $this->bekukanJam('06:45');
        $this->scan(KodePresensi::payload($this->santri->kode_presensi));

        // Antrean padat, kartu tersenggol dua kali — dan kali ini sudah lewat batas.
        $this->bekukanJam('07:40');
        $this->scan(KodePresensi::payload($this->santri->kode_presensi))
            ->assertSee('Sudah tercatat');

        // Kalau jam pertama ditimpa, santri yang datang tepat waktu lalu lewat lagi
        // setelah batas akan berubah jadi terlambat — hukuman untuk hal yang tidak
        // ia lakukan.
        $this->assertSame(1, Presensi::withoutGlobalScope('pesantren')->count());
        $this->assertSame(
            StatusKehadiran::Hadir,
            Presensi::withoutGlobalScope('pesantren')->first()->status
        );
    }

    public function test_nis_diterima_saat_kartu_tertinggal(): void
    {
        $this->bekukanJam('06:45');

        $this->scan($this->santri->nis)->assertSee('Ahmad Fauzi');

        $this->assertSame(1, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_kode_tanpa_prefiks_tetap_diterima(): void
    {
        $this->bekukanJam('06:45');

        // Petugas mengetik kode dari kartu tanpa prefiks — wajar, karena yang
        // tercetak sebagai teks di kartu memang kodenya saja.
        $this->scan($this->santri->kode_presensi)->assertSee('Ahmad Fauzi');

        $this->assertSame(1, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_kode_milik_pesantren_lain_tidak_ditemukan(): void
    {
        $lain = Pesantren::factory()->create();
        $santriLain = Santri::factory()->create(['pesantren_id' => $lain->id]);

        $this->scan(KodePresensi::payload($santriLain->kode_presensi))
            ->assertSee('Tidak dikenali');

        $this->assertSame(0, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_kode_ngawur_ditolak_dengan_pesan(): void
    {
        $this->scan('WSP1.TIDAKADA0000')->assertSee('Tidak dikenali');

        $this->assertSame(0, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_santri_non_aktif_ditolak(): void
    {
        $nonAktif = Santri::factory()->nonAktif()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
        ]);

        $this->scan(KodePresensi::payload($nonAktif->kode_presensi))
            ->assertSee('non-aktif');

        $this->assertSame(0, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_ustadz_hanya_bisa_memindai_kelas_perwaliannya(): void
    {
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);

        $kelasLain = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id, 'nama_kelas' => 'Lain']);
        $this->kelas->update(['wali_kelas_id' => $ustadz->id]);

        $santriLuar = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelasLain->id,
        ]);

        $this->bekukanJam('06:45');

        $this->scan(KodePresensi::payload($santriLuar->kode_presensi), $ustadz)
            ->assertSee('luar kelas perwalian');

        $this->assertSame(0, Presensi::withoutGlobalScope('pesantren')->count());

        $this->scan(KodePresensi::payload($this->santri->kode_presensi), $ustadz)
            ->assertSee('Hadir.');

        $this->assertSame(1, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_halaman_menawarkan_kamera_di_samping_jalur_alat_pemindai(): void
    {
        // Kamera adalah lapis KEDUA; kolom teks ber-autofocus tetap jalur utama
        // karena ia bekerja tanpa JavaScript dan bisa diuji di sini.
        Livewire::actingAs($this->admin)
            ->test(PresensiScannerPage::class)
            ->assertSee('Pindai dengan Kamera')
            ->assertSee('pemindai-kamera', escape: false)
            // Bundel html5-qrcode dimuat HANYA di halaman ini, bukan sebagai aset
            // panel — ~370 KB tidak boleh membebani setiap halaman admin.
            ->assertSee('presensi-scanner', escape: false);
    }

    public function test_wali_santri_tidak_bisa_membuka_halaman_scan(): void
    {
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);

        $this->actingAs($wali);

        $this->assertFalse(PresensiScannerPage::canAccess());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}

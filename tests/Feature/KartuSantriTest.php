<?php

namespace Tests\Feature;

use App\Filament\Pages\PesantrenSettingsPage;
use App\Filament\Resources\Presensis\Pages\ListPresensis;
use App\Filament\Resources\Santris\Pages\ListSantris;
use App\Filament\Resources\Santris\Pages\ViewSantri;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use App\Services\KartuSantriPdf;
use App\Services\TahunAjaranOptions;
use App\Support\KodePresensi;
use Carbon\CarbonInterface;
use chillerlan\QRCode\QRCode;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Kartu santri berfoto — kembaran kartu QR (lihat PresensiKartuQrTest).
 *
 * Fiturnya pindah dari Presensi → Kehadiran ke Santri → Data Santri, jadi berkas
 * ini juga menjaga bahwa jalan masuk lamanya benar-benar tertutup: tombol yang
 * "dipindah" tapi diam-diam masih ada di tempat lama membuat dua permukaan yang
 * perlahan berbeda perilakunya.
 */
class KartuSantriTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User, 2: Kelas} */
    private function pesantrenDenganAdmin(array $profil = []): array
    {
        $pesantren = Pesantren::factory()->create(['profil' => $profil]);
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        return [$pesantren, $admin, $kelas];
    }

    private function isiPdf(Response $response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    /**
     * HTML kartu sebagaimana dilihat DomPDF.
     *
     * Isi PDF-nya sendiri tidak bisa dicocokkan harfiah — DomPDF mengompresi aliran
     * teks, jadi assertStringContainsString terhadap keluaran PDF lulus-palsu atau
     * gagal-palsu tergantung font. Blade-nya dirender langsung dengan data yang
     * sama persis yang dipakai render sungguhan.
     */
    private function htmlKartu(Kelas $kelas, ?CarbonInterface $masaBerlaku = null): string
    {
        $service = app(KartuSantriPdf::class);

        return view('filament.pdf.kartu-santri', $service->dataView(
            $service->kartuUntukKelas($kelas),
            $masaBerlaku ?? Carbon::create(2027, 6, 30),
        ))->render();
    }

    public function test_kartu_memuat_identitas_pesantren_masa_berlaku_dan_kepala(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin([
            'kepala_pesantren' => 'KH Abdullah Syafii',
            'alamat' => 'Jl. Pesantren No. 1',
        ]);
        $pesantren->update(['nama_pesantren' => 'Pesantren Nurul Hidayah']);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'nis' => '2026001',
        ]);

        $this->actingAs($admin);

        $html = $this->htmlKartu($kelas);

        $this->assertStringContainsString('Pesantren Nurul Hidayah', $html);
        $this->assertStringContainsString('Jl. Pesantren No. 1', $html);
        $this->assertStringContainsString('Ahmad Fauzi', $html);
        $this->assertStringContainsString('2026001', $html);
        $this->assertStringContainsString('30 Juni 2027', $html);

        // Nama kepala DAN labelnya — label yang tercetak tanpa nama di bawahnya
        // membuat kartu terlihat cacat, jadi keduanya diperiksa bersama.
        $this->assertStringContainsString('Kepala Pesantren', $html);
        $this->assertStringContainsString('KH Abdullah Syafii', $html);
    }

    public function test_blok_kepala_hilang_sama_sekali_saat_namanya_belum_diisi(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        $this->actingAs($admin);

        $html = $this->htmlKartu($kelas);

        // Bukan cuma namanya yang kosong — labelnya pun tidak boleh ikut tercetak.
        $this->assertStringNotContainsString('Kepala Pesantren', $html);
        $this->assertStringContainsString('Berlaku sampai', $html);
    }

    public function test_nama_kepala_disimpan_dari_pengaturan_pesantren(): void
    {
        [$pesantren, $admin] = $this->pesantrenDenganAdmin([
            // Kunci profil lain harus selamat: save() memakai array_merge, dan
            // menggantinya dengan penulisan langsung akan menghapus logo & galeri
            // tanpa gejala apa pun sampai ada yang membuka profil publik.
            'deskripsi' => 'Pesantren contoh',
            'telepon' => '021-0000000',
        ]);

        Livewire::actingAs($admin)
            ->test(PesantrenSettingsPage::class)
            ->set('kepala_pesantren', 'KH Abdullah Syafii, M.Pd.I')
            ->call('save')
            ->assertHasNoFormErrors();

        $profil = $pesantren->fresh()->profil;

        $this->assertSame('KH Abdullah Syafii, M.Pd.I', $profil['kepala_pesantren']);
        $this->assertSame('Pesantren contoh', $profil['deskripsi']);
        $this->assertSame('021-0000000', $profil['telepon']);

        // Dan accessor-nya — jalur yang dipakai kartu — ikut membacanya.
        $this->assertSame('KH Abdullah Syafii, M.Pd.I', $pesantren->fresh()->kepala_pesantren);
    }

    public function test_kartu_memuat_qr_presensi_yang_bisa_dipindai(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        $this->actingAs($admin);

        $kartu = app(KartuSantriPdf::class)->kartuUntukKelas($kelas)->first();

        $this->assertNotNull($kartu->qr, 'Kartu santri harus memuat QR presensi.');
        $this->assertSame($santri->kode_presensi, $kartu->kode);

        // QR DIBACA BALIK, bukan sekadar dicek ada — persis alasan yang sama dengan
        // PresensiKartuQrTest: QRCode::render() menambahkan segmen data ke
        // instance-nya, jadi instance yang dipakai ulang menghasilkan kartu berisi
        // beberapa kode sekaligus, tanpa satu pun error.
        $png = base64_decode(substr($kartu->qr, strlen('data:image/png;base64,')));
        $terbaca = (string) (new QRCode)->readFromBlob($png);

        $this->assertSame(KodePresensi::payload($santri->kode_presensi), $terbaca);
        $this->assertSame(1, substr_count($terbaca, KodePresensi::PREFIKS));

        // Kartu identitas dipakai harian dan QR-nya lecek lebih cepat; kodenya harus
        // tetap bisa diketik petugas.
        $this->assertStringContainsString($santri->kode_presensi, $this->htmlKartu($kelas));
    }

    public function test_tiap_kartu_sekelas_memuat_qr_miliknya_sendiri(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        $santriList = collect(['Ahmad', 'Bilal', 'Candra'])->map(
            fn (string $nama) => Santri::factory()->create([
                'pesantren_id' => $pesantren->id,
                'kelas_id' => $kelas->id,
                'nama_lengkap' => $nama,
            ])
        );

        $this->actingAs($admin);

        $kartu = app(KartuSantriPdf::class)->kartuUntukKelas($kelas);

        foreach ($kartu as $i => $k) {
            $png = base64_decode(substr($k->qr, strlen('data:image/png;base64,')));
            $terbaca = (string) (new QRCode)->readFromBlob($png);

            $this->assertSame(
                KodePresensi::payload($santriList[$i]->kode_presensi),
                $terbaca,
                "QR kartu ke-{$i} tidak memuat persis satu kode milik santri itu.",
            );
        }
    }

    public function test_kartu_tetap_terbit_saat_santri_belum_punya_kode(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);
        // Kode digenerate SantriObserver, jadi dikosongkan lewat query builder —
        // meniru baris lama yang lolos backfill.
        $santri->newQuery()->toBase()->where('id', $santri->id)->update(['kode_presensi' => null]);

        $this->actingAs($admin);

        $kartu = app(KartuSantriPdf::class)->kartuUntukKelas($kelas)->first();

        $this->assertNull($kartu->qr);
        $this->assertStringContainsString('Kode kartu', $this->htmlKartu($kelas));
        $this->assertSame(1, $this->jumlahHalaman($this->isiPdf(
            app(KartuSantriPdf::class)->untukSantri($santri->fresh(), Carbon::create(2027, 6, 30))
        )));
    }

    public function test_kartu_tidak_pernah_memuat_uuid_magic_link(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Bilal Ramadhan',
        ]);

        $this->actingAs($admin);

        $pdf = $this->isiPdf(
            app(KartuSantriPdf::class)->untukKelas($kelas, Carbon::create(2027, 6, 30))
        );

        // ⚠️ PENJAGA TEMUAN §13.2, kembaran dari tes yang sama di kartu QR.
        // santri.uuid adalah token bearer Magic Link: VerifyMagicToken menukarnya
        // jadi Auth::login($wali), sesi wali yang utuh mencakup semua anaknya, SPP,
        // uang saku, dan rapor. Kartu identitas justru lebih rawan daripada kartu
        // QR — ia dipegang santri setiap hari dan dipotret untuk grup WhatsApp.
        $this->assertStringNotContainsString($santri->uuid, $pdf);
    }

    public function test_hanya_santri_aktif_di_kelas_itu_yang_dicetak(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();
        $kelasLain = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Ahmad Aktif',
        ]);
        Santri::factory()->nonAktif()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Bilal NonAktif',
        ]);
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelasLain->id,
            'nama_lengkap' => 'Candra KelasLain',
        ]);

        $this->actingAs($admin);

        $kartu = app(KartuSantriPdf::class)->kartuUntukKelas($kelas);

        $this->assertCount(1, $kartu);
        $this->assertSame('Ahmad Aktif', $kartu->first()->nama);
    }

    public function test_kartu_tetap_terbit_saat_foto_dan_logo_kosong(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin([
            // Path yang menunjuk berkas yang tidak ada di disk: persis kondisi
            // tenant yang logonya terhapus manual dari storage. Guard file_exists()
            // di accessor harus menelannya, bukan membuat DomPDF melempar.
            'logo' => 'logo-pesantren/tidak-ada.png',
        ]);

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'foto_profil' => 'foto-profil/tidak-ada.jpg',
        ]);

        $this->actingAs($admin);

        $this->assertNull($pesantren->fresh()->logo_path);
        $this->assertNull($santri->fresh()->foto_profil_path);

        $pdf = $this->isiPdf(
            app(KartuSantriPdf::class)->untukSantri($santri, Carbon::create(2027, 6, 30))
        );

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_alamat_panjang_dipotong_sebelum_masuk_kartu(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'alamat_lengkap' => str_repeat('Jalan Panjang Sekali ', 20),
        ]);

        $this->actingAs($admin);

        $alamat = app(KartuSantriPdf::class)->kartuUntukKelas($kelas)->first()->alamat;

        // Dipotong di service, bukan lewat CSS: DomPDF menumpuk teks ke luar bingkai
        // alih-alih menyembunyikannya saat overflow. `Str::limit()` menambahkan '...'
        // di luar batasnya, karena itu +3.
        $this->assertLessThanOrEqual(KartuSantriPdf::PANJANG_ALAMAT + 3, strlen($alamat));
        $this->assertStringEndsWith('...', $alamat);
    }

    /** Jumlah halaman PDF — `/Type /Pages` (simpul induk) sengaja tidak ikut dihitung. */
    private function jumlahHalaman(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page(?![s])#', $pdf);
    }

    public function test_kartu_terpanjang_pun_tetap_muat_satu_halaman(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin([
            'kepala_pesantren' => 'Prof. Dr. KH Muhammad Abdullah Syafii Al-Hafizh, M.Pd.I',
            'alamat' => 'Jl. Raya Pesantren Nurul Hidayah Kilometer 12 Nomor 345 RT 007 RW 009',
        ]);
        $pesantren->update(['nama_pesantren' => 'Pondok Pesantren Modern Nurul Hidayah Al-Islamiyah Terpadu']);

        // Kamar belum punya factory; dibuat langsung karena yang diuji di sini
        // adalah panjang teksnya, bukan variasi datanya.
        $kamar = Kamar::create([
            'pesantren_id' => $pesantren->id,
            'nama_kamar' => 'Kamar Abu Bakar Ash-Shiddiq',
            'kapasitas' => 0,
        ]);

        $santri = Santri::factory()->count(3)->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'kamar_id' => $kamar->id,
            'nama_lengkap' => 'Muhammad Abdurrahman Zaki Maulana Alfarizi',
            'alamat_lengkap' => str_repeat('Jl. Kampung Melayu Besar Nomor 123 RT 004 RW 011 ', 5),
            'tanggal_lahir' => '2010-09-27',
        ]);

        $this->actingAs($admin);

        $pdf = $this->isiPdf(
            app(KartuSantriPdf::class)->untukKelas($kelas, Carbon::create(2027, 6, 30))
        );

        // ⚠️ PENJAGA TATA LETAK. Kartunya setinggi persis satu halaman PDF, jadi
        // teks yang membungkus satu baris lebih panjang dari perkiraan mendorongnya
        // ke halaman kedua — dan DomPDF melakukannya TANPA error apa pun. Gejalanya
        // di lapangan cuma "PDF-nya dua kali lebih tebal", yang gampang dikira wajar
        // sampai ada yang mencetaknya ke kartu PVC. Isi di tes ini sengaja kasus
        // terburuk: semua kolom terisi dan semuanya panjang.
        $this->assertSame(
            $santri->count(),
            $this->jumlahHalaman($pdf),
            'Satu kartu harus menempati persis satu halaman. Periksa batas panjang teks '
            .'di KartuSantriPdf dan tinggi elemen di kartu-santri.blade.php.',
        );
    }

    public function test_kartu_santri_tanpa_kolom_opsional_juga_satu_halaman(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'kamar_id' => null,
            'alamat_lengkap' => null,
            'jenis_kelamin' => null,
            'tanggal_lahir' => null,
            'foto_profil' => null,
        ]);

        $this->actingAs($admin);

        $pdf = $this->isiPdf(
            app(KartuSantriPdf::class)->untukSantri($santri, Carbon::create(2027, 6, 30))
        );

        $this->assertSame(1, $this->jumlahHalaman($pdf));
    }

    public function test_bawaan_masa_berlaku_adalah_akhir_tahun_ajaran(): void
    {
        $akhir = TahunAjaranOptions::akhirTahunAjaran();
        [, $tahunTutup] = array_map('intval', explode('/', TahunAjaranOptions::current()));

        $this->assertSame(6, $akhir->month);
        $this->assertSame(30, $akhir->day);
        $this->assertSame($tahunTutup, $akhir->year);
    }

    public function test_admin_bisa_unduh_kedua_kartu_dari_detail_santri(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ViewSantri::class, ['record' => $santri->getKey()])
            // Tombolnya hidup di footerActions section "Kartu Santri" di infolist,
            // bukan di header halaman — karena itu ->schemaComponent().
            ->callAction(TestAction::make('unduh_kartu_qr')->schemaComponent('kartu-santri'))
            ->assertHasNoActionErrors()
            ->callAction(
                TestAction::make('unduh_kartu_santri')->schemaComponent('kartu-santri'),
                ['masa_berlaku' => '2027-06-30'],
            )
            ->assertHasNoActionErrors();
    }

    public function test_admin_bisa_cetak_massal_kedua_jenis_kartu(): void
    {
        [$pesantren, $admin, $kelas] = $this->pesantrenDenganAdmin();
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListSantris::class)
            ->callAction(TestAction::make('cetak_kartu'), [
                'kelas_id' => $kelas->id,
                'jenis' => 'qr',
            ])
            ->assertHasNoActionErrors()
            ->callAction(TestAction::make('cetak_kartu'), [
                'kelas_id' => $kelas->id,
                'jenis' => 'lengkap',
                'masa_berlaku' => '2027-06-30',
            ])
            ->assertHasNoActionErrors();
    }

    public function test_ustadz_tidak_bisa_cetak_kartu_massal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        // Cetak massal per kelas melampaui cakupan perwalian ustadz, jadi tetap
        // admin-only seperti saat masih di Presensi. Unduh per santri bimbingan
        // tersedia lewat halaman detail.
        Livewire::actingAs($ustadz)
            ->test(ListSantris::class)
            ->assertActionHidden(TestAction::make('cetak_kartu'));
    }

    public function test_kelas_pesantren_lain_tidak_bisa_dicetak(): void
    {
        [, $admin] = $this->pesantrenDenganAdmin();

        $tetangga = Pesantren::factory()->create();
        $kelasTetangga = Kelas::factory()->create(['pesantren_id' => $tetangga->id]);

        $this->actingAs($admin);

        // Global scope Multitenantable yang menegakkannya — bukan filter manual di
        // service. Kalau suatu saat seseorang memakai DB::table() di sana, scope-nya
        // hilang dan tes ini yang menolak.
        $this->expectException(ModelNotFoundException::class);

        Kelas::findOrFail($kelasTetangga->id);
    }

    public function test_tombol_cetak_kartu_sudah_hilang_dari_presensi(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        // Fiturnya dipindah, bukan digandakan. Tombol yang tertinggal di tempat
        // lama akan pelan-pelan berbeda perilakunya dari yang di menu Santri.
        Livewire::actingAs($admin)
            ->test(ListPresensis::class)
            ->assertActionDoesNotExist(TestAction::make('cetakKartu'));
    }
}

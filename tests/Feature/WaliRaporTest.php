<?php

namespace Tests\Feature;

use App\Models\KesantrianKarakterRapor;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TahfidzUjian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class WaliRaporTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $wali;

    private Santri $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->wali = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);
        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $this->wali->id,
            'nama_lengkap' => 'Anak Wali Pertama',
            'status_aktif' => true,
        ]);
    }

    /** Santri milik wali lain, tetap di pesantren yang sama. */
    private function santriTetangga(): Santri
    {
        $waliLain = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $waliLain->id,
            'nama_lengkap' => 'Anak Keluarga Lain',
            'status_aktif' => true,
        ]);
    }

    private function buatKarakter(Santri $santri, array $atribut = []): KesantrianKarakterRapor
    {
        return KesantrianKarakterRapor::create(array_merge([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'tanggal_input' => '2026-11-10',
            'log_kasus_khusus' => null,
        ], $atribut));
    }

    private function buatNilai(Santri $santri, array $atribut = []): NilaiAkademik
    {
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_mapel' => $atribut['nama_mapel'] ?? 'Nahwu',
        ]);
        unset($atribut['nama_mapel']);

        return NilaiAkademik::create(array_merge([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ], $atribut));
    }

    /**
     * Tangkap data yang dikirim ke template PDF. Isi PDF hasil DomPDF terkompresi
     * sehingga tidak bisa di-assert langsung dari byte-nya.
     *
     * @param  array<string, mixed>  $tangkapan
     */
    private function rekamDataPdf(array &$tangkapan): void
    {
        View::composer('wali.pdf.laporan', function ($view) use (&$tangkapan) {
            $tangkapan = $view->getData();
        });
    }

    // ── B1: kebocoran data antar-wali ────────────────────────────────────

    public function test_wali_tidak_bisa_melihat_rapor_santri_wali_lain(): void
    {
        $tetangga = $this->santriTetangga();
        $this->buatKarakter($tetangga, ['log_kasus_khusus' => 'Rahasia keluarga lain']);
        $this->buatNilai($tetangga, ['nama_mapel' => 'MapelRahasia', 'nilai' => 42]);

        // Global scope Multitenantable hanya menyaring pesantren_id, jadi tanpa
        // pengecekan pemilik, santri_id dari query string cukup untuk membaca rapor
        // keluarga lain di pesantren yang sama.
        $this->actingAs($this->wali)
            ->get(route('wali.rapor', ['santri_id' => $tetangga->id, 'tab' => 'karakter']))
            ->assertOk()
            ->assertDontSee('Rahasia keluarga lain');

        $this->actingAs($this->wali)
            ->get(route('wali.rapor', ['santri_id' => $tetangga->id, 'tab' => 'akademik']))
            ->assertOk()
            ->assertDontSee('MapelRahasia');
    }

    public function test_santri_id_tidak_valid_jatuh_ke_anak_sendiri(): void
    {
        $this->buatNilai($this->santri, ['nama_mapel' => 'Fiqih Anak Sendiri']);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor', ['santri_id' => 999999, 'tab' => 'akademik']))
            ->assertOk()
            ->assertSee('Fiqih Anak Sendiri');
    }

    // ── B2: filter karakter memakai kolom periode, bukan tanggal_input ───

    public function test_karakter_tahun_ajaran_berikutnya_tetap_tampil(): void
    {
        // Filter lama (tanggal_input LIKE '2026%') membuang record yang diinput
        // di paruh kedua tahun ajaran 2026/2027.
        $this->buatKarakter($this->santri, [
            'periode' => 'Semester_Genap',
            'tanggal_input' => '2027-02-20',
            'log_kasus_khusus' => 'Catatan semester genap',
        ]);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor', ['tahun_ajaran' => '2026/2027', 'tab' => 'karakter']))
            ->assertOk()
            ->assertSee('Catatan semester genap')
            ->assertSee('Semester Genap');
    }

    public function test_karakter_menampilkan_semua_periode_dalam_satu_tahun_ajaran(): void
    {
        $this->buatKarakter($this->santri, [
            'periode' => 'Semester_Ganjil',
            'tanggal_input' => '2026-11-10',
            'log_kasus_khusus' => 'Catatan ganjil',
        ]);

        $this->buatKarakter($this->santri, [
            'periode' => 'Bulanan',
            'bulan' => '12-2026',
            'tanggal_input' => '2026-12-28',
            'log_kasus_khusus' => 'Catatan bulanan',
        ]);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor', ['tahun_ajaran' => '2026/2027', 'tab' => 'karakter']))
            ->assertOk()
            ->assertSee('Catatan ganjil')
            ->assertSee('Catatan bulanan')
            ->assertSee('Desember 2026');
    }

    public function test_karakter_tahun_ajaran_lain_tidak_ikut_tampil(): void
    {
        $this->buatKarakter($this->santri, [
            'tahun_ajaran' => '2025/2026',
            'log_kasus_khusus' => 'Catatan tahun lalu',
        ]);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor', ['tahun_ajaran' => '2026/2027', 'tab' => 'karakter']))
            ->assertOk()
            ->assertDontSee('Catatan tahun lalu');
    }

    // ── B4: dropdown tahun ajaran tidak lagi hanya dari tahfidz ──────────

    public function test_tahun_ajaran_dari_nilai_akademik_muncul_di_dropdown(): void
    {
        // Santri ini tidak pernah ujian tahfidz, jadi sumber lama (TahfidzUjian)
        // tidak menghasilkan opsi apa pun untuk tahun ajaran lampau.
        $this->buatNilai($this->santri, ['tahun_ajaran' => '2024/2025']);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor'))
            ->assertOk()
            ->assertSee('2024/2025');
    }

    public function test_tahun_ajaran_dari_rapor_karakter_muncul_di_dropdown(): void
    {
        $this->buatKarakter($this->santri, ['tahun_ajaran' => '2023/2024']);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor'))
            ->assertOk()
            ->assertSee('2023/2024');
    }

    // ── B3: PDF ──────────────────────────────────────────────────────────

    public function test_pdf_wali_memuat_rapor_karakter(): void
    {
        // Pemetaan lama ke periode 'Semester' sudah dihapus dari CHECK constraint,
        // jadi seksi karakter di PDF praktis selalu kosong.
        $this->buatKarakter($this->santri, ['log_kasus_khusus' => 'Perlu pendampingan wudhu']);

        $data = [];
        $this->rekamDataPdf($data);

        $respons = $this->actingAs($this->wali)
            ->get(route('wali.laporan.pdf', [
                'santri_id' => $this->santri->id,
                'tahun_ajaran' => '2026/2027',
            ]));

        $respons->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respons->getContent());

        $this->assertCount(1, $data['raporKarakter']);
        $this->assertSame('Perlu pendampingan wudhu', $data['raporKarakter']->first()->log_kasus_khusus);
    }

    public function test_pdf_wali_merangkum_seluruh_periode_tahun_ajaran(): void
    {
        TahfidzUjian::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal_ujian' => '2026-11-10',
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => '85',
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'B',
            'nilai_tajwid' => 'A',
            'rekomendasi_pembimbing' => 'Pertahankan murajaah.',
        ]);

        TahfidzUjian::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal_ujian' => '2027-05-10',
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Genap',
            'nilai_hafalan' => '90',
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'A',
            'nilai_tajwid' => 'A',
            'rekomendasi_pembimbing' => 'Naik target juz.',
        ]);

        // Sebelumnya PDF hanya memuat satu periode (currentPeriode di-hardcode),
        // sehingga isinya tidak sama dengan yang tampil di halaman.
        $data = [];
        $this->rekamDataPdf($data);

        $respons = $this->actingAs($this->wali)
            ->get(route('wali.laporan.pdf', [
                'santri_id' => $this->santri->id,
                'tahun_ajaran' => '2026/2027',
            ]));

        $respons->assertOk();
        $this->assertStringStartsWith('%PDF', $respons->getContent());

        $this->assertCount(2, $data['raporTahfidz']);
        $this->assertEqualsCanonicalizing(
            ['Semester_Ganjil', 'Semester_Genap'],
            $data['raporTahfidz']->pluck('periode')->all(),
        );
    }

    public function test_pdf_wali_menolak_santri_bukan_anaknya(): void
    {
        $tetangga = $this->santriTetangga();

        $this->actingAs($this->wali)
            ->get(route('wali.laporan.pdf', [
                'santri_id' => $tetangga->id,
                'tahun_ajaran' => '2026/2027',
            ]))
            ->assertNotFound();
    }
}

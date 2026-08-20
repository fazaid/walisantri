<?php

namespace Tests\Feature;

use App\Filament\Pages\EditProfile;
use App\Filament\Resources\Santris\Pages\ListSantris;
use App\Filament\Resources\Santris\SantriResource;
use App\Filament\Support\Panduan;
use App\Models\Pesantren;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol "Panduan" di baris judul tiap halaman panel.
 *
 * Yang dijanjikan ke pengguna adalah tombol yang BENAR-BENAR TAMPAK di halaman,
 * jadi sebagian tes di sini memeriksa HALAMAN YANG DIRENDER, bukan nilai
 * kembalian method — sejalan dengan ModulNavigasiTest dan TombolBantuanTest.
 */
class TombolPanduanTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($this->pesantren);
        Filament::setCurrentPanel('admin');
    }

    private function admin(): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
    }

    private function ustadz(): User
    {
        return User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
    }

    public function test_tombol_muncul_di_halaman_yang_punya_panduan(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/santri/santris')
            ->assertOk()
            // Id modalnya, bukan kata "Panduan": halaman santri juga punya
            // Placeholder berlabel "Panduan" di modal Import Excel.
            ->assertSee('panduan-halaman')
            ->assertSee('Daftar induk seluruh santri', false);
    }

    public function test_tombol_tidak_muncul_di_halaman_tanpa_entri(): void
    {
        // Halaman profil bawaan Filament: bukan "menu" yang dituju orang untuk
        // mengerjakan sesuatu, dan formnya menjelaskan dirinya sendiri.
        $this->assertNull(
            Panduan::untuk(EditProfile::class),
            'Tes ini mengandaikan EditProfile memang tidak punya entri panduan.',
        );

        $this->actingAs($this->admin())
            ->get('/admin/profile')
            ->assertOk()
            ->assertDontSee('panduan-halaman');
    }

    public function test_catatan_ustadz_hanya_tampil_untuk_ustadz(): void
    {
        $catatan = Panduan::untuk(SantriResource::class)['ustadz'];

        $this->actingAs($this->ustadz())
            ->get('/admin/santri/santris')
            ->assertOk()
            ->assertSee($catatan, false);

        $this->actingAs($this->admin())
            ->get('/admin/santri/santris')
            ->assertOk()
            ->assertDontSee($catatan, false);
    }

    /**
     * Kasus paling rawan: halaman yang headernya TIDAK punya tombol aksi apa pun.
     * Wadah .fi-header-actions-ctn baru dirender kalau salah satu dari hook-before,
     * actions, atau hook-after terisi — jadi kalau suatu saat Filament mengubah
     * syarat itu, halaman inilah yang pertama kehilangan tombolnya.
     */
    public function test_tombol_muncul_juga_di_halaman_tanpa_aksi_header(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/pengaturan')
            ->assertOk()
            ->assertSee('panduan-halaman')
            ->assertSee('Identitas dan profil publik pesantren', false);
    }

    public function test_tombol_muncul_di_halaman_kustom_tersembunyi(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/presensi/isi-presensi')
            ->assertOk()
            ->assertSee('panduan-halaman')
            ->assertSee('Mencatat kehadiran satu kelas untuk satu tanggal', false);
    }

    /**
     * Penjaga kelengkapan — inti berkas ini.
     *
     * Menuntut KEPUTUSAN SADAR untuk setiap Resource/Page: ditulis panduannya,
     * atau didaftarkan sebagai sengaja-tanpa-panduan. Tanpa tes ini, menu baru
     * akan diam-diam lahir tanpa tombol Panduan dan tidak ada yang tahu sampai
     * ada pengurus yang menanyakannya.
     */
    public function test_setiap_komponen_panel_punya_keputusan_panduan(): void
    {
        $panel = Filament::getPanel('admin');
        $terdaftar = [...array_keys(Panduan::PETA), ...Panduan::TANPA_PANDUAN];

        foreach ([...$panel->getResources(), ...$panel->getPages()] as $kelas) {
            $this->assertContains(
                $kelas,
                $terdaftar,
                "{$kelas} belum diputuskan di App\\Filament\\Support\\Panduan — tambahkan entrinya di PETA, "
                .'atau daftarkan di TANPA_PANDUAN beserta alasannya.',
            );
        }
    }

    /**
     * Kebalikannya: entri yang menunjuk kelas yang sudah tidak ada lagi di panel.
     * Tanpa ini, naskah panduan untuk resource yang sudah dihapus akan menumpuk
     * tanpa gejala apa pun.
     */
    public function test_tidak_ada_entri_untuk_kelas_yang_sudah_tidak_terdaftar(): void
    {
        $panel = Filament::getPanel('admin');
        $adaDiPanel = [...$panel->getResources(), ...$panel->getPages()];

        foreach ([...array_keys(Panduan::PETA), ...Panduan::TANPA_PANDUAN] as $kelas) {
            $this->assertContains(
                $kelas,
                $adaDiPanel,
                "{$kelas} terdaftar di Panduan tetapi bukan komponen panel admin lagi — hapus entrinya.",
            );
        }
    }

    public function test_bentuk_setiap_entri_konsisten(): void
    {
        foreach (Panduan::PETA as $kelas => $entri) {
            $this->assertNotEmpty($entri['judul'] ?? '', "{$kelas}: judul kosong.");
            $this->assertNotEmpty($entri['ringkas'] ?? '', "{$kelas}: ringkas kosong.");
            $this->assertStringEndsWith('.', $entri['ringkas'], "{$kelas}: ringkas harus satu kalimat berakhir titik.");
            $this->assertGreaterThanOrEqual(2, count($entri['langkah'] ?? []), "{$kelas}: minimal 2 langkah.");
            $this->assertLessThanOrEqual(6, count($entri['langkah']), "{$kelas}: maksimal 6 langkah — selebihnya milik /panduan.");
        }
    }

    /**
     * Presedensi scope: entri atas nama kelas halaman menang atas entri resource.
     * Diuji sebagai fungsi murni, tanpa merender apa pun.
     */
    public function test_entri_halaman_menang_atas_entri_resource(): void
    {
        $this->assertNull(Panduan::untukScope([]));
        $this->assertNull(Panduan::untukScope(['App\\Filament\\Pages\\TidakAda']));

        $this->assertSame(
            Panduan::untuk(SantriResource::class),
            Panduan::untukScope([
                ListSantris::class,
                SantriResource::class,
            ]),
        );
    }

    public function test_sub_halaman_memakai_panduan_resource_induknya(): void
    {
        $judul = Panduan::untuk(SantriResource::class)['judul'];

        $this->actingAs($this->admin())
            ->get(SantriResource::getUrl('index'))
            ->assertOk()
            ->assertSee($judul);
    }

    /**
     * Tautan "Baca panduan lengkap" yang meleset tidak akan pernah memunculkan
     * galat — ia hanya mendarat di puncak halaman. Hanya tes yang bisa menjaganya.
     */
    public function test_setiap_anchor_menunjuk_seksi_yang_ada(): void
    {
        $halaman = file_get_contents(resource_path('views/panduan.blade.php'));

        foreach (Panduan::PETA as $kelas => $entri) {
            if (! isset($entri['anchor'])) {
                continue;
            }

            $this->assertStringContainsString(
                'id="'.$entri['anchor'].'"',
                $halaman,
                "Anchor \"{$entri['anchor']}\" ({$kelas}) tidak ada di panduan.blade.php.",
            );
        }
    }
}

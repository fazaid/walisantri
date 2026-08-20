<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\PlatformBrandingSetting;
use App\Models\PlatformContactSetting;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol WhatsApp melayang di pojok kanan bawah halaman situs.
 *
 * Dua tes di berkas ini menjaga KEPUTUSAN, bukan mekanisme — keduanya tidak akan
 * menimbulkan galat apa pun bila dilanggar, jadi hanya tes yang bisa menjaganya:
 *
 * - test_tombol_tidak_dipasang_di_profil_publik_pesantren — halaman itu milik
 *   pesantren, dan nomor ini nomor vendor. Memasangnya di sana mengalihkan calon
 *   wali ke pihak yang salah.
 * - test_warna_merek_ditulis_literal_bukan_lewat_palet — mode gelap halaman situs
 *   membalik variabel palet, sehingga `text-white` jadi gelap dan `bg-green-*`
 *   bergeser. Hijau WhatsApp harus tetap hijau di kedua mode.
 */
class TombolWhatsAppMelayangTest extends TestCase
{
    use RefreshDatabase;

    private const NOMOR = '0812-3456-7890';

    private const NOMOR_RAPI = '6281234567890';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        PlatformBrandingSetting::set('wa_dukungan', self::NOMOR);
    }

    /** @return array<string, string> */
    private function halamanSitus(): array
    {
        $base = 'http://'.config('app.base_domain');

        return [
            'landing' => $base.'/',
            'harga' => $base.'/harga',
            'demo' => $base.'/demo',
            'panduan' => 'http://'.config('app.domain').'/panduan',
        ];
    }

    public function test_nomor_cs_dipakai_sebagai_cadangan_saat_wa_dukungan_kosong(): void
    {
        PlatformBrandingSetting::set('wa_dukungan', null);
        // cs_whatsapp disimpan sudah dalam format internasional — berbeda dari
        // wa_dukungan yang dirapikan sendiri oleh modelnya (lihat kedua model).
        PlatformContactSetting::set('cs_whatsapp', '628111112222');
        PlatformSetting::set('registration_open', true);

        $this->get('http://'.config('app.base_domain').'/')
            ->assertOk()
            ->assertSee('https://wa.me/628111112222', false);
    }

    public function test_tombol_tampil_di_semua_halaman_situs(): void
    {
        PlatformSetting::set('registration_open', true);
        PlatformSetting::set('demo_open', true);

        foreach ($this->halamanSitus() as $nama => $url) {
            $isi = $this->get($url)->assertOk("gagal render: {$nama}")->getContent();

            $this->assertStringContainsString(
                'https://wa.me/'.self::NOMOR_RAPI,
                $isi,
                "tombol WhatsApp tidak ada di halaman {$nama}.",
            );
            $this->assertStringContainsString('Hubungi Kami', $isi, "label hilang di {$nama}.");
        }
    }

    public function test_tombol_tidak_muncul_saat_nomor_belum_diisi(): void
    {
        // Keduanya: nomor CS adalah cadangan yang punya nilai bawaan dari
        // migrasi, jadi mengosongkan wa_dukungan saja tidak menghilangkan tombol.
        PlatformBrandingSetting::set('wa_dukungan', null);
        PlatformContactSetting::set('cs_whatsapp', null);
        PlatformSetting::set('registration_open', true);
        PlatformSetting::set('demo_open', true);

        foreach ($this->halamanSitus() as $nama => $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('wa.me', false);
        }
    }

    /**
     * Berbeda dari tombol Hubungi Kami di kartu paket /harga, yang sengaja hilang
     * saat kedua pintu tertutup. Ini kontak dukungan umum, bukan ajakan
     * berlangganan — orang tetap boleh bertanya.
     */
    public function test_tombol_tetap_tampil_saat_pendaftaran_dan_demo_ditutup(): void
    {
        PlatformSetting::set('registration_open', false);
        PlatformSetting::set('demo_open', false);

        $base = 'http://'.config('app.base_domain');

        foreach (['landing' => $base.'/', 'harga' => $base.'/harga'] as $nama => $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('https://wa.me/'.self::NOMOR_RAPI, false);
        }
    }

    public function test_tombol_tidak_dipasang_di_profil_publik_pesantren(): void
    {
        $pesantren = Pesantren::factory()->create();

        $this->get($this->urlTenant($pesantren, '/'))
            ->assertOk()
            ->assertDontSee('wa.me', false);
    }

    public function test_warna_merek_ditulis_literal_bukan_lewat_palet(): void
    {
        $partial = file_get_contents(resource_path('views/partials/tombol-whatsapp.blade.php'));
        $kelas = str($partial)->after('class="fixed')->before('"')->toString();

        $this->assertStringContainsString('bg-[#25D366]', $kelas, 'hijau WhatsApp harus nilai literal.');
        $this->assertStringNotContainsString('text-white', $kelas, 'text-white dibalik jadi gelap di mode gelap.');
        $this->assertStringNotContainsString('bg-green-', $kelas, 'palet green ikut dibalik di mode gelap.');
    }
}

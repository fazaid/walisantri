<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliTemaTest extends TestCase
{
    use RefreshDatabase;

    private function santriDenganWali(): Santri
    {
        $pesantren = Pesantren::factory()->create();
        // §1.8 Fase 1: permukaan wali hidup di host pesantren, jadi seluruh
        // route('wali.*') di berkas ini harus menunjuk ke sana.
        $this->pakaiHostTenant($pesantren);
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif' => true,
        ]);
    }

    /**
     * Portal wali memakai partial tema yang sama dengan halaman publik — itulah yang
     * membuat pilihan pembaca terbawa dari landing ke portal, bukan dua setelan
     * terpisah. Yang dijaga di sini bagian yang bisa hilang diam-diam saat menyunting
     * layout: skripnya wajib ikut ter-render (kalau tidak, wali bermode gelap kena
     * kedip putih tiap membuka halaman) dan tombolnya ada di header.
     */
    public function test_portal_wali_memakai_pemilih_tema_yang_sama_dengan_halaman_publik(): void
    {
        $this->withoutVite();
        $santri = $this->santriDenganWali();

        $this->actingAs($santri->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('data-tema-tombol', false)
            ->assertSee('localStorage.getItem(KUNCI)', false)
            ->assertSee('Ganti mode terang/gelap');
    }

    /**
     * Header dan kartu identitas adalah permukaan besar berwarna merek, bukan tombol.
     * Tanpa penguncian `dark:`, paletnya membalik dan keduanya berubah jadi blok teal
     * menyala — persis kelas cacat yang ditutup di landing v4.46.
     */
    public function test_permukaan_bermerek_dikunci_agar_tidak_menyala_di_mode_gelap(): void
    {
        $this->withoutVite();
        $santri = $this->santriDenganWali();

        $this->actingAs($santri->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('bg-teal-700 text-white dark:bg-teal-100 dark:text-gray-900', false);
    }
}
